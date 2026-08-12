<?php

namespace Tests\Feature\Auth;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirect_sends_the_user_to_google(): void
    {
        Socialite::fake('google');

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
    }

    public function test_new_google_user_is_registered_with_own_family(): void
    {
        $this->fakeGoogleUser([
            'id' => 'google-123',
            'name' => 'グーグル太郎',
            'email' => 'google-taro@example.com',
        ]);

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('home', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'google-taro@example.com')->firstOrFail();

        $this->assertSame('google-123', $user->google_id);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);

        $family = Family::where('owner_id', $user->id)->firstOrFail();

        $this->assertSame('グーグル太郎の家族', $family->name);
        $this->assertSame($family->id, session('current_family_id'));
    }

    public function test_google_login_links_to_an_existing_account_with_the_same_email(): void
    {
        $existing = User::factory()->create(['email' => 'existing@example.com']);
        $family = Family::factory()->create(['owner_id' => $existing->id]);
        $family->members()->attach($existing->id, ['role' => 'owner']);

        $this->fakeGoogleUser([
            'id' => 'google-456',
            'name' => '別名でもよい',
            'email' => 'existing@example.com',
        ]);

        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame('google-456', $existing->fresh()->google_id);
        $this->assertSame($family->id, session('current_family_id'));

        // 既存ユーザーに新しい家族を作らない
        $this->assertSame(1, Family::where('owner_id', $existing->id)->count());
        $this->assertSame(1, User::where('email', 'existing@example.com')->count());
    }

    public function test_google_login_is_rejected_when_the_email_is_not_verified(): void
    {
        User::factory()->create(['email' => 'victim@example.com']);

        $this->fakeGoogleUser(
            [
                'id' => 'google-789',
                'name' => 'なりすまし',
                'email' => 'victim@example.com',
            ],
            emailVerified: false,
        );

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHasErrors('email');
        $this->assertNull(User::where('email', 'victim@example.com')->firstOrFail()->google_id);
    }

    public function test_second_google_login_uses_the_same_user(): void
    {
        $user = User::factory()->create([
            'email' => 'repeat@example.com',
            'google_id' => 'google-999',
            'password' => null,
        ]);
        $family = Family::factory()->create(['owner_id' => $user->id]);
        $family->members()->attach($user->id, ['role' => 'owner']);

        $this->fakeGoogleUser([
            'id' => 'google-999',
            'name' => 'リピーター',
            'email' => 'repeat@example.com',
        ]);

        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame(1, User::count());
        $this->assertSame(1, Family::count());
        $this->assertSame($family->id, session('current_family_id'));
    }

    public function test_cancelled_google_login_returns_to_the_login_screen(): void
    {
        Socialite::fake('google');

        $response = $this->get(route('auth.google.callback', ['error' => 'access_denied']));

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHasErrors('email');
    }

    /**
     * Googleから返るユーザー情報をモックする
     *
     * @param  array<string, mixed>  $attributes
     */
    private function fakeGoogleUser(array $attributes, bool $emailVerified = true): void
    {
        Socialite::fake('google', SocialiteUser::fake(array_merge($attributes, ['email_verified' => $emailVerified])));
    }
}

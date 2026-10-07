<?php

namespace Tests\Feature\Auth;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered_with_invite(): void
    {
        $family = Family::factory()->create();

        $response = $this->withSession(['invite_family_code' => $family->code])->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_redirects_to_login_without_invite(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', '新規登録は家族からの招待リンクからのみ行えます。');
    }

    public function test_registration_screen_redirects_to_login_with_expired_invite(): void
    {
        $family = Family::factory()->create(['code_expires_at' => now()->subDay()]);

        $response = $this->withSession([
            'invite_family_code' => $family->code,
            'invite_role' => 'parent',
        ])->get('/register');

        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', '招待リンクが無効または期限切れです。家族に新しい招待リンクを発行してもらってください。');

        // 無効な招待はセッションから破棄する
        $response->assertSessionMissing('invite_family_code');
        $response->assertSessionMissing('invite_role');
    }

    public function test_new_users_cannot_register_without_invite(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertSame(0, Family::count());
    }

    public function test_new_users_cannot_register_when_invited_family_is_full(): void
    {
        $family = Family::factory()->create(['max_members' => 1]);
        $family->members()->attach(User::factory()->create()->id, ['role' => 'owner']);

        $response = $this->withSession(['invite_family_code' => $family->code])->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
        $response->assertSessionHas('status', '招待先の家族が定員に達しているため登録できません。');
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_with_invite_joins_the_invited_family(): void
    {
        $family = Family::factory()->create();

        // 招待URLアクセスでセッションに招待情報が入った状態
        $this->withSession([
            'invite_family_code' => $family->code,
            'invite_role' => 'parent',
        ]);

        $response = $this->post('/register', [
            'name' => 'はなこ',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));

        $user = User::where('email', 'hanako@example.com')->firstOrFail();

        $this->assertSame('parent', $family->members()->where('users.id', $user->id)->firstOrFail()->pivot->role);
        $this->assertSame($family->id, session('current_family_id'));
        $this->assertSame($family->id, $user->fresh()->last_family_id);

        // 招待経由では本人の家族を自動作成しない
        $this->assertDatabaseMissing('families', ['owner_id' => $user->id]);
        $this->assertCount(1, $user->families);
    }
}

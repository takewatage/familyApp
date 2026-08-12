<?php

namespace Tests\Feature\Auth;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'testtest',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_selects_the_only_family_the_user_belongs_to(): void
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id]);
        $family->members()->attach($user->id, ['role' => 'owner']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'testtest',
        ]);

        $this->assertAuthenticated();
        $this->assertSame($family->id, session('current_family_id'));
    }

    public function test_login_selects_the_last_used_family(): void
    {
        $user = User::factory()->create();
        $first = Family::factory()->create(['owner_id' => $user->id]);
        $second = Family::factory()->create(['owner_id' => $user->id]);

        $first->members()->attach($user->id, ['role' => 'owner']);
        $second->members()->attach($user->id, ['role' => 'parent']);

        $user->forceFill(['last_family_id' => $second->id])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'testtest',
        ]);

        $this->assertSame($second->id, session('current_family_id'));
    }

    public function test_login_falls_back_when_the_last_used_family_is_no_longer_joined(): void
    {
        $user = User::factory()->create();
        $joined = Family::factory()->create(['owner_id' => $user->id]);
        $left = Family::factory()->create();

        $joined->members()->attach($user->id, ['role' => 'owner']);

        // 所属していない家族IDが last_family_id に残っているケース
        $user->forceFill(['last_family_id' => $left->id])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'testtest',
        ]);

        $this->assertSame($joined->id, session('current_family_id'));
    }

    public function test_users_without_family_can_still_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'testtest',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
        $this->assertNull(session('current_family_id'));
    }

    public function test_google_only_user_can_not_authenticate_with_password(): void
    {
        $user = User::factory()->create([
            'password' => null,
            'google_id' => 'google-123',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'testtest',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_registration_without_invite_creates_own_family(): void
    {
        $this->post('/register', [
            'name' => 'たろう',
            'email' => 'taro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $family = Family::where('owner_id', $user->id)->firstOrFail();

        $this->assertSame('たろうの家族', $family->name);
        $this->assertNotEmpty($family->code);
        $this->assertSame('owner', $family->members()->where('users.id', $user->id)->firstOrFail()->pivot->role);
        $this->assertSame($family->id, session('current_family_id'));
        $this->assertSame($family->id, $user->fresh()->last_family_id);
    }

    public function test_registration_with_invite_joins_the_invited_family(): void
    {
        $family = Family::factory()->create();

        // 招待URLアクセスでセッションに招待情報が入った状態
        $this->withSession([
            'invite_family_code' => $family->code,
            'invite_role' => 'parent',
        ]);

        $this->post('/register', [
            'name' => 'はなこ',
            'email' => 'hanako@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'hanako@example.com')->firstOrFail();

        $this->assertSame('parent', $family->members()->where('users.id', $user->id)->firstOrFail()->pivot->role);
        $this->assertSame($family->id, session('current_family_id'));

        // 招待経由では本人の家族を自動作成しない
        $this->assertDatabaseMissing('families', ['owner_id' => $user->id]);
        $this->assertCount(1, $user->families);
    }
}

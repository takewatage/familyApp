<?php

namespace Tests\Feature\Console;

use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateFamilyTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_family_with_new_owner(): void
    {
        $this->artisan('family:create', ['name' => 'テスト家', '--email' => 'Owner@Example.com'])
            ->expectsQuestion('オーナーの名前', 'おーなー')
            ->expectsQuestion('パスワード', 'password')
            ->expectsQuestion('パスワード（確認）', 'password')
            ->assertSuccessful();

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $family = Family::where('owner_id', $owner->id)->firstOrFail();

        $this->assertSame('おーなー', $owner->name);
        $this->assertTrue(Hash::check('password', $owner->password));
        $this->assertNotNull($owner->email_verified_at);
        $this->assertSame('テスト家', $family->name);
        $this->assertNotEmpty($family->code);
        $this->assertSame('owner', $family->members()->where('users.id', $owner->id)->firstOrFail()->pivot->role);
        $this->assertSame($family->id, $owner->last_family_id);
    }

    public function test_existing_user_becomes_owner_of_new_family(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->artisan('family:create', ['name' => '二つ目の家族', '--email' => 'me@example.com'])
            ->assertSuccessful();

        $family = Family::where('owner_id', $user->id)->firstOrFail();

        $this->assertSame('二つ目の家族', $family->name);
        $this->assertSame(1, User::count());
        $this->assertSame($family->id, $user->fresh()->last_family_id);
    }

    public function test_fails_when_password_confirmation_does_not_match(): void
    {
        $this->artisan('family:create', ['name' => 'テスト家', '--email' => 'owner@example.com'])
            ->expectsQuestion('オーナーの名前', 'おーなー')
            ->expectsQuestion('パスワード', 'password')
            ->expectsQuestion('パスワード（確認）', 'different')
            ->assertFailed();

        $this->assertSame(0, User::count());
        $this->assertSame(0, Family::count());
    }
}

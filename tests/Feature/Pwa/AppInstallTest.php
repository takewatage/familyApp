<?php

namespace Tests\Feature\Pwa;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AppInstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_app_install_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('mypage.app-install'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MyPage/AppInstall')
                ->has('pwa.name')
                ->has('pwa.iconApple'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('mypage.app-install'))->assertRedirect(route('login'));
    }
}

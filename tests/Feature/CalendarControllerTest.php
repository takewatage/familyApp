<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_calendar_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calendar'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Calendar/Index'));
    }

    public function test_index_redirects_guest_to_login(): void
    {
        $this->get(route('calendar'))->assertRedirect(route('login'));
    }
}

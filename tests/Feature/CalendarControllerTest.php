<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\User;
use App\Models\VirtualUser;
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

    public function test_index_passes_family_members_and_virtual_users_as_participants(): void
    {
        $owner = User::factory()->create(['name' => 'パパ']);
        $member = User::factory()->create(['name' => 'ママ']);
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'owner']);
        $family->members()->attach($member->id, ['role' => 'parent']);
        VirtualUser::create(['family_id' => $family->id, 'name' => 'こども']);

        // 別の家族のメンバーは含まない
        $other = Family::factory()->create();
        $other->members()->attach(User::factory()->create(['name' => 'よその人'])->id, ['role' => 'owner']);

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('calendar'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Calendar/Index')
                ->has('participants', 3)
                // 家族メンバー（参加順）→ 仮想ユーザーの順
                ->where('participants', fn ($participants) => collect($participants)
                    ->map(fn ($p) => [$p['name'], $p['isVirtual']])
                    ->sort()->values()->all() === [['こども', true], ['パパ', false], ['ママ', false]])
                ->where('participants.2.isVirtual', true));
    }

    public function test_participants_are_ordered_by_join_date(): void
    {
        $owner = User::factory()->create(['name' => 'あとから参加']);
        $first = User::factory()->create(['name' => '先に参加']);
        $family = Family::factory()->create(['owner_id' => $owner->id]);
        $family->members()->attach($owner->id, ['role' => 'owner', 'created_at' => now()]);
        $family->members()->attach($first->id, ['role' => 'parent', 'created_at' => now()->subDay()]);

        $this->actingAs($owner)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('calendar'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('participants.0.name', '先に参加')
                ->where('participants.1.name', 'あとから参加'));
    }

    public function test_index_passes_no_participants_without_family(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('calendar'))
            ->assertInertia(fn (Assert $page) => $page->has('participants', 0));
    }

    public function test_index_redirects_guest_to_login(): void
    {
        $this->get(route('calendar'))->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature\Calendar;

use App\Models\CalendarLabel;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'たろう', 'birthday' => '1990-10-15']);
        $this->family = Family::factory()->create([
            'owner_id' => $this->user->id,
            'settings' => ['pwa' => ['name' => 'うちのアプリ']],
        ]);
        $this->family->members()->attach($this->user->id, ['role' => 'child']);
    }

    private function inFamily(): static
    {
        return $this->actingAs($this->user)->withSession(['current_family_id' => $this->family->id]);
    }

    public function test_birthday_label_can_be_set_by_any_member_and_is_applied_to_birthdays(): void
    {
        $label = CalendarLabel::factory()->create(['family_id' => $this->family->id]);

        // 未設定なら誕生日はラベルなし（画面の既定色）
        $this->inFamily()->getJson(route('calendar.events.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertJsonPath('events.0.labelId', null);

        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_label_id' => $label->id])
            ->assertOk()
            ->assertJsonPath('settings.birthdayLabelId', $label->id);

        $this->inFamily()->getJson(route('calendar.events.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertJsonPath('events.0.isBirthday', true)
            ->assertJsonPath('events.0.labelId', $label->id);

        $this->inFamily()->get(route('calendar'))
            ->assertInertia(fn (Assert $page) => $page->where('settings.birthdayLabelId', $label->id));

        // 他の家族設定（PWA）は消えない
        $this->assertSame('うちのアプリ', $this->family->fresh()->settings['pwa']['name']);
    }

    public function test_birthday_label_can_be_cleared(): void
    {
        $label = CalendarLabel::factory()->create(['family_id' => $this->family->id]);
        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_label_id' => $label->id])->assertOk();

        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_label_id' => null])
            ->assertOk()
            ->assertJsonPath('settings.birthdayLabelId', null);
    }

    public function test_other_family_label_cannot_be_used(): void
    {
        $other = CalendarLabel::factory()->create();

        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_label_id' => $other->id])
            ->assertJsonValidationErrors('birthday_label_id');

        $this->assertArrayNotHasKey('calendar', $this->family->fresh()->settings);
    }
}

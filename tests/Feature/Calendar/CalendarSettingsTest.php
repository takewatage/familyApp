<?php

namespace Tests\Feature\Calendar;

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

    public function test_birthday_color_can_be_set_by_any_member(): void
    {
        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_color' => '#1e88e5'])
            ->assertOk()
            ->assertJsonPath('settings.birthdayColor', '#1e88e5');

        $this->inFamily()->get(route('calendar'))
            ->assertInertia(fn (Assert $page) => $page->where('settings.birthdayColor', '#1e88e5'));

        // 誕生日の予定はラベルなし（色はカレンダー設定で画面が決める）
        $this->inFamily()->getJson(route('calendar.events.index', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertJsonPath('events.0.isBirthday', true)
            ->assertJsonPath('events.0.labelId', null);

        // 他の家族設定（PWA）は消えない
        $this->assertSame('うちのアプリ', $this->family->fresh()->settings['pwa']['name']);
    }

    public function test_birthday_color_can_be_reset_to_default(): void
    {
        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_color' => '#1e88e5'])->assertOk();

        $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_color' => null])
            ->assertOk()
            ->assertJsonPath('settings.birthdayColor', null);
    }

    public function test_birthday_color_must_be_hex(): void
    {
        foreach (['red', '#12345', '#GGGGGG', 'rgb(0,0,0)'] as $invalid) {
            $this->inFamily()->putJson(route('calendar.settings.update'), ['birthday_color' => $invalid])
                ->assertJsonValidationErrors('birthday_color');
        }

        $this->assertArrayNotHasKey('calendar', $this->family->fresh()->settings);
    }
}

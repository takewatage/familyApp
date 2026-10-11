<?php

namespace Tests\Feature\Calendar;

use App\Models\CalendarEvent;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CalendarHolidayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $other;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        // 誕生日が祝日の件数・並びに混ざらないよう、誕生日なしにする
        $this->user = User::factory()->create(['birthday' => null, 'settings' => ['theme' => 'dark']]);
        $this->other = User::factory()->create(['birthday' => null]);
        $this->family = Family::factory()->create([
            'owner_id' => $this->user->id,
            'settings' => ['calendar' => ['birthday_color' => '#1e88e5']],
        ]);
        $this->family->members()->attach($this->user->id, ['role' => 'parent']);
        $this->family->members()->attach($this->other->id, ['role' => 'child']);
    }

    private function as(User $user): static
    {
        return $this->actingAs($user)->withSession(['current_family_id' => $this->family->id]);
    }

    private function events(User $user, string $from, string $to): TestResponse
    {
        return $this->as($user)->getJson(route('calendar.events.index', ['from' => $from, 'to' => $to]))->assertOk();
    }

    public function test_holidays_are_included_and_placed_before_events_on_the_same_day(): void
    {
        CalendarEvent::factory()->create([
            'family_id' => $this->family->id,
            'created_by' => $this->user->id,
            'title' => 'お出かけ',
            'all_day' => true,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-06',
        ]);

        $events = $this->events($this->user, '2026-05-06', '2026-05-06')->json('events');

        $this->assertSame(['振替休日', 'お出かけ'], array_column($events, 'title'));
        $this->assertSame('holiday:2026-05-06', $events[0]['id']);
        $this->assertTrue($events[0]['isHoliday']);
        $this->assertTrue($events[0]['allDay']);
        $this->assertSame([], $events[0]['participantIds']);
        $this->assertFalse($events[1]['isHoliday']);
    }

    /**
     * 祝日より前に始まる複数日の予定があっても、その日の中では祝日を先頭に返す（並びは画面でもそろえる）
     */
    public function test_holiday_is_listed_with_multi_day_event_crossing_it(): void
    {
        CalendarEvent::factory()->create([
            'family_id' => $this->family->id,
            'created_by' => $this->user->id,
            'title' => '旅行',
            'all_day' => true,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-04',
        ]);

        $titles = array_column($this->events($this->user, '2026-11-01', '2026-11-30')->json('events'), 'title');

        $this->assertSame(['旅行', '文化の日', '勤労感謝の日'], $titles);
    }

    public function test_settings_saved_on_mypage_keep_show_holidays(): void
    {
        $this->as($this->user)->putJson(route('calendar.settings.update'), ['show_holidays' => false])->assertOk();

        $this->actingAs($this->user)->post(route('mypage.setting.theme-color.update'), [
            'theme' => 'light', 'theme_name' => 'ocean', 'footer_items' => ['home'],
        ])->assertSessionHasNoErrors();

        $settings = $this->user->fresh()->settings;
        $this->assertSame('ocean', $settings['theme_name']);
        $this->assertFalse($settings['calendar']['show_holidays']);
    }

    public function test_bridge_holiday_is_included(): void
    {
        $titles = array_column($this->events($this->user, '2026-09-21', '2026-09-23')->json('events'), 'title');

        $this->assertSame(['敬老の日', '国民の休日', '秋分の日'], $titles);
    }

    public function test_hiding_holidays_applies_only_to_that_user(): void
    {
        $this->as($this->user)->putJson(route('calendar.settings.update'), ['show_holidays' => false])
            ->assertOk()
            ->assertJsonPath('settings.showHolidays', false)
            ->assertJsonPath('settings.birthdayColor', '#1e88e5');

        $this->assertSame([], $this->events($this->user, '2026-11-01', '2026-11-30')->json('events'));
        $this->assertSame(
            ['文化の日', '勤労感謝の日'],
            array_column($this->events($this->other, '2026-11-01', '2026-11-30')->json('events'), 'title'),
        );

        $this->as($this->user)->get(route('calendar'))
            ->assertInertia(fn (Assert $page) => $page->where('settings.showHolidays', false));
        $this->as($this->other)->get(route('calendar'))
            ->assertInertia(fn (Assert $page) => $page->where('settings.showHolidays', true));

        // 表示に戻せる
        $this->as($this->user)->putJson(route('calendar.settings.update'), ['show_holidays' => true])
            ->assertJsonPath('settings.showHolidays', true);
        $this->assertCount(2, $this->events($this->user, '2026-11-01', '2026-11-30')->json('events'));
    }

    public function test_updating_show_holidays_keeps_other_settings(): void
    {
        $this->as($this->user)->putJson(route('calendar.settings.update'), ['show_holidays' => false])->assertOk();

        $user = $this->user->fresh();
        $this->assertSame('dark', $user->settings['theme']);
        $this->assertFalse($user->settings['calendar']['show_holidays']);

        // 家族の設定には保存しない（誕生日のカラーも消えない）
        $this->assertSame(['birthday_color' => '#1e88e5'], $this->family->fresh()->settings['calendar']);

        // 誕生日のカラーを変えても祝日の設定は消えない
        $this->as($this->user)->putJson(route('calendar.settings.update'), ['birthday_color' => '#e53935'])
            ->assertJsonPath('settings.showHolidays', false)
            ->assertJsonPath('settings.birthdayColor', '#e53935');
        $this->assertArrayNotHasKey('calendar', $this->other->fresh()->settings ?? []);
    }

    public function test_show_holidays_must_be_boolean(): void
    {
        foreach (['yes', null, 'abc'] as $invalid) {
            $this->as($this->user)->putJson(route('calendar.settings.update'), ['show_holidays' => $invalid])
                ->assertJsonValidationErrors('show_holidays');
        }

        $this->assertArrayNotHasKey('calendar', $this->user->fresh()->settings);
    }

    public function test_holidays_cannot_be_updated_or_deleted(): void
    {
        $this->as($this->user)->putJson(route('calendar.events.update', 'holiday:2026-11-03'), [
            'title' => '書き換え', 'all_day' => true, 'start_date' => '2026-11-03', 'end_date' => '2026-11-03',
        ])->assertNotFound();

        $this->as($this->user)->deleteJson(route('calendar.events.destroy', 'holiday:2026-11-03'))->assertNotFound();
    }
}

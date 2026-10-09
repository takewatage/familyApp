<?php

namespace Tests\Feature\Calendar;

use App\Models\CalendarEvent;
use App\Models\CalendarLabel;
use App\Models\Family;
use App\Models\User;
use App\Models\VirtualUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['birthday' => null]);
        $this->family = Family::factory()->create(['owner_id' => $this->user->id]);
        $this->family->members()->attach($this->user->id, ['role' => 'owner']);
    }

    private function actingInFamily(): static
    {
        return $this->actingAs($this->user)->withSession(['current_family_id' => $this->family->id]);
    }

    private function fetch(string $from, string $to): TestResponse
    {
        return $this->actingInFamily()->getJson(route('calendar.events.index', ['from' => $from, 'to' => $to]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function events(string $from, string $to): array
    {
        return $this->fetch($from, $to)->assertOk()->json('events');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => '予定',
            'all_day' => true,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'participant_ids' => [],
        ], $overrides);
    }

    private function recurring(string $rrule, string $start = '2026-10-05', array $attrs = []): CalendarEvent
    {
        return CalendarEvent::factory()->recurring($rrule)->create(array_merge([
            'family_id' => $this->family->id,
            'title' => '繰り返し',
            'start_date' => $start,
            'end_date' => $start,
        ], $attrs));
    }

    // ---------------------------------------------------------------- 取得

    public function test_fetch_requires_valid_range(): void
    {
        $this->fetch('2026-10-31', '2026-10-01')->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->fetch('2026-01-01', '2026-12-31')->assertUnprocessable()->assertJsonValidationErrors('to');
    }

    public function test_single_and_multi_day_events_overlapping_the_range_are_returned(): void
    {
        CalendarEvent::factory()->create(['family_id' => $this->family->id, 'title' => '前月から続く', 'start_date' => '2026-09-29', 'end_date' => '2026-10-02']);
        CalendarEvent::factory()->timed('09:00', '10:30')->create(['family_id' => $this->family->id, 'title' => '時刻あり', 'start_date' => '2026-10-10', 'end_date' => '2026-10-10']);
        CalendarEvent::factory()->create(['family_id' => $this->family->id, 'title' => '期間外', 'start_date' => '2026-11-05', 'end_date' => '2026-11-05']);

        $events = collect($this->events('2026-10-01', '2026-10-31'))->keyBy('title');

        $this->assertSame(['前月から続く', '時刻あり'], $events->keys()->all());
        $this->assertSame('09:00', $events['時刻あり']['startTime']);
        $this->assertSame('10:30', $events['時刻あり']['endTime']);
        $this->assertFalse($events['時刻あり']['allDay']);
    }

    public function test_other_family_events_are_not_returned(): void
    {
        CalendarEvent::factory()->create(['title' => 'よその家族', 'start_date' => '2026-10-10', 'end_date' => '2026-10-10']);

        $this->assertSame([], $this->events('2026-10-01', '2026-10-31'));
    }

    public function test_weekly_event_is_expanded_except_exdates(): void
    {
        $event = $this->recurring('FREQ=WEEKLY;BYDAY=MO');
        $event->exdates()->create(['date' => '2026-10-12']);

        $dates = collect($this->events('2026-10-01', '2026-10-31'))->pluck('occurrenceDate')->all();

        $this->assertSame(['2026-10-05', '2026-10-19', '2026-10-26'], $dates);
    }

    public function test_monthly_yearly_until_and_count_rules(): void
    {
        $this->recurring('FREQ=MONTHLY;BYMONTHDAY=31', '2026-01-31', ['title' => '31日']);
        $this->recurring('FREQ=MONTHLY;BYDAY=2TU', '2026-01-13', ['title' => '第2火曜']);
        $this->recurring('FREQ=YEARLY', '2020-10-20', ['title' => '毎年']);
        $this->recurring('FREQ=DAILY;UNTIL=20261003', '2026-10-01', ['title' => '3日まで']);
        $this->recurring('FREQ=DAILY;COUNT=2', '2026-10-01', ['title' => '2回']);

        $events = collect($this->events('2026-10-01', '2026-10-31'))->groupBy('title')
            ->map(fn ($g) => $g->pluck('occurrenceDate')->all());

        $this->assertSame(['2026-10-31'], $events['31日']);
        $this->assertSame(['2026-10-13'], $events['第2火曜']);
        $this->assertSame(['2026-10-20'], $events['毎年']);
        $this->assertSame(['2026-10-01', '2026-10-02', '2026-10-03'], $events['3日まで']);
        $this->assertSame(['2026-10-01', '2026-10-02'], $events['2回']);
    }

    public function test_multi_day_recurring_event_includes_occurrence_started_before_range(): void
    {
        $this->recurring('FREQ=WEEKLY', '2026-09-27', ['end_date' => '2026-09-29']);

        // 9/27〜9/29 の回は、9/29 だけを取得しても含まれる
        $first = $this->events('2026-09-29', '2026-09-29')[0];

        $this->assertSame('2026-09-27', $first['startDate']);
        $this->assertSame('2026-09-29', $first['endDate']);
        $this->assertSame([], $this->events('2026-09-30', '2026-10-03'));
    }

    public function test_birthdays_are_returned_as_read_only_events(): void
    {
        $this->user->update(['name' => 'たろう', 'birthday' => '1990-10-15']);
        $leap = User::factory()->create(['name' => 'うるう', 'birthday' => '2000-02-29']);
        $this->family->members()->attach($leap->id, ['role' => 'parent']);

        $events = $this->events('2026-10-01', '2026-10-31');

        $this->assertCount(1, $events);
        $this->assertSame('たろうの誕生日', $events[0]['title']);
        $this->assertSame('2026-10-15', $events[0]['startDate']);
        $this->assertTrue($events[0]['isBirthday']);
        $this->assertSame([$this->user->id], $events[0]['participantIds']);

        // うるう年以外の 2/29 生まれは 2/28 に表示する
        $this->assertSame('2027-02-28', $this->events('2027-02-01', '2027-02-28')[0]['startDate']);
    }

    // ---------------------------------------------------------------- 作成・更新・削除（単発）

    public function test_create_event_with_label_participants_and_time(): void
    {
        $label = CalendarLabel::factory()->create(['family_id' => $this->family->id]);
        $virtual = VirtualUser::create(['family_id' => $this->family->id, 'name' => 'こども']);

        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload([
            'title' => '面談',
            'memo' => '持ち物あり',
            'all_day' => false,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'label_id' => $label->id,
            'participant_ids' => [$this->user->id, $virtual->id],
        ]))->assertCreated();

        $event = CalendarEvent::firstOrFail();

        $this->assertSame('面談', $event->title);
        $this->assertSame('持ち物あり', $event->memo);
        $this->assertSame('持ち物あり', $this->events('2026-10-01', '2026-10-31')[0]['memo']);
        $this->assertSame($this->user->id, $event->created_by);
        $this->assertSame($label->id, $event->label_id);
        $this->assertSame(
            [[User::class, $this->user->id], [VirtualUser::class, $virtual->id]],
            $event->participants->map(fn ($p) => [$p->participant_type, $p->participant_id])->all(),
        );
    }

    public function test_create_rejects_other_family_label_participant_and_invalid_rrule(): void
    {
        $otherLabel = CalendarLabel::factory()->create();
        $stranger = User::factory()->create();

        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload(['label_id' => $otherLabel->id]))
            ->assertJsonValidationErrors('label_id');
        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload(['participant_ids' => [$stranger->id]]))
            ->assertJsonValidationErrors('participant_ids');
        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload(['rrule' => 'FREQ=SECONDLY']))
            ->assertJsonValidationErrors('rrule');
        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload(['all_day' => false]))
            ->assertJsonValidationErrors('start_time');

        $this->assertSame(0, CalendarEvent::count());
    }

    public function test_update_and_delete_single_event(): void
    {
        $event = CalendarEvent::factory()->create(['family_id' => $this->family->id, 'start_date' => '2026-10-05', 'end_date' => '2026-10-05']);

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload(['title' => '変更後', 'start_date' => '2026-10-06', 'end_date' => '2026-10-07']))
            ->assertOk();

        $this->assertSame('変更後', $event->fresh()->title);
        $this->assertSame('2026-10-07', $event->fresh()->end_date->toDateString());

        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $event->id))->assertOk();

        $this->assertSoftDeleted($event);
    }

    public function test_other_family_event_cannot_be_updated_or_deleted(): void
    {
        $event = CalendarEvent::factory()->create();

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload())->assertNotFound();
        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $event->id))->assertNotFound();
    }

    // ---------------------------------------------------------------- 繰り返しの変更

    public function test_update_this_occurrence_creates_override(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload([
            'title' => 'この回だけ',
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-13',
            'scope' => 'this',
            'occurrence_date' => '2026-10-12',
        ]))->assertOk();

        $events = collect($this->events('2026-10-01', '2026-10-31'));

        $this->assertSame(
            [['繰り返し', '2026-10-05'], ['この回だけ', '2026-10-13'], ['繰り返し', '2026-10-19'], ['繰り返し', '2026-10-26']],
            $events->map(fn ($e) => [$e['title'], $e['startDate']])->all(),
        );

        $override = $events->firstWhere('title', 'この回だけ');
        $this->assertTrue($override['isRecurring']);
        $this->assertSame('2026-10-12', $override['occurrenceDate']);
        $this->assertSame('FREQ=WEEKLY', $override['rrule']);

        // 上書き予定を「この回だけ」でもう一度変更すると、同じ行を更新する
        $this->actingInFamily()->putJson(route('calendar.events.update', $override['id']), $this->payload([
            'title' => '再変更', 'start_date' => '2026-10-13', 'end_date' => '2026-10-13', 'scope' => 'this',
        ]))->assertOk();

        $this->assertSame(1, CalendarEvent::whereNotNull('recurring_event_id')->count());
        $this->assertSame('再変更', CalendarEvent::whereNotNull('recurring_event_id')->first()->title);
    }

    public function test_update_this_rejects_date_outside_the_series(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload(['scope' => 'this', 'occurrence_date' => '2026-10-13']))
            ->assertJsonValidationErrors('occurrence_date');
    }

    public function test_update_following_splits_the_series(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');
        $event->exdates()->create(['date' => '2026-10-26']);

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload([
            'title' => 'これ以降',
            'start_date' => '2026-10-19',
            'end_date' => '2026-10-19',
            'rrule' => 'FREQ=WEEKLY',
            'scope' => 'following',
            'occurrence_date' => '2026-10-19',
        ]))->assertOk();

        $this->assertSame('FREQ=WEEKLY;UNTIL=20261018', $event->fresh()->rrule);
        // 分割後の日付の除外日は取り消す
        $this->assertSame(0, $event->exdates()->count());

        $events = collect($this->events('2026-10-01', '2026-10-31'));

        $this->assertSame(
            [['繰り返し', '2026-10-05'], ['繰り返し', '2026-10-12'], ['これ以降', '2026-10-19'], ['これ以降', '2026-10-26']],
            $events->map(fn ($e) => [$e['title'], $e['startDate']])->all(),
        );
    }

    public function test_update_all_shifts_the_series_and_resets_exceptions_when_dates_change(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');
        $event->exdates()->create(['date' => '2026-10-12']);

        // 2026-10-19 の回を 1 日後ろにずらして「すべて」変更 → 繰り返し全体が 1 日ずれる
        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload([
            'title' => 'すべて',
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-20',
            'rrule' => 'FREQ=WEEKLY',
            'scope' => 'all',
            'occurrence_date' => '2026-10-19',
        ]))->assertOk();

        $this->assertSame('2026-10-06', $event->fresh()->start_date->toDateString());
        $this->assertSame(0, $event->exdates()->count());
        $this->assertSame(
            ['2026-10-06', '2026-10-13', '2026-10-20', '2026-10-27'],
            collect($this->events('2026-10-01', '2026-10-31'))->pluck('startDate')->all(),
        );
    }

    public function test_update_all_keeps_exceptions_when_only_the_title_changes(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');
        $event->exdates()->create(['date' => '2026-10-12']);

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload([
            'title' => '名前だけ変更', 'rrule' => 'FREQ=WEEKLY', 'scope' => 'all', 'occurrence_date' => '2026-10-05',
        ]))->assertOk();

        $this->assertSame(1, $event->exdates()->count());
        $this->assertSame(['名前だけ変更'], collect($this->events('2026-10-01', '2026-10-31'))->pluck('title')->unique()->values()->all());
    }

    // ---------------------------------------------------------------- 繰り返しの削除

    public function test_delete_this_following_and_all(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');

        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $event->id), ['scope' => 'this', 'occurrence_date' => '2026-10-12'])->assertOk();
        $this->assertSame(['2026-10-05', '2026-10-19', '2026-10-26'], collect($this->events('2026-10-01', '2026-10-31'))->pluck('startDate')->all());

        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $event->id), ['scope' => 'following', 'occurrence_date' => '2026-10-19'])->assertOk();
        $this->assertSame(['2026-10-05'], collect($this->events('2026-10-01', '2026-10-31'))->pluck('startDate')->all());

        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $event->id), ['scope' => 'all'])->assertOk();
        $this->assertSame([], $this->events('2026-10-01', '2026-10-31'));
        $this->assertSoftDeleted($event);
    }

    public function test_delete_override_this_adds_exdate_and_following_from_first_deletes_all(): void
    {
        $event = $this->recurring('FREQ=WEEKLY');
        $override = CalendarEvent::factory()->create([
            'family_id' => $this->family->id, 'title' => '上書き', 'start_date' => '2026-10-13', 'end_date' => '2026-10-13',
            'recurring_event_id' => $event->id, 'original_date' => '2026-10-12',
        ]);

        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $override->id), ['scope' => 'this'])->assertOk();

        $this->assertModelMissing($override);
        $this->assertSame(['2026-10-05', '2026-10-19', '2026-10-26'], collect($this->events('2026-10-01', '2026-10-31'))->pluck('startDate')->all());

        // 最初の回からの「これ以降」は「すべて」と同じ
        $this->actingInFamily()->deleteJson(route('calendar.events.destroy', $event->id), ['scope' => 'following', 'occurrence_date' => '2026-10-05'])->assertOk();
        $this->assertSoftDeleted($event);
    }

    // ---------------------------------------------------------------- レビュー指摘の回帰

    public function test_update_all_from_moved_override_does_not_shift_the_series(): void
    {
        $event = $this->recurring('FREQ=WEEKLY;BYDAY=MO');
        $override = CalendarEvent::factory()->create([
            'family_id' => $this->family->id, 'title' => '火曜に移動', 'start_date' => '2026-10-13', 'end_date' => '2026-10-13',
            'recurring_event_id' => $event->id, 'original_date' => '2026-10-12',
        ]);

        // 上書き予定の日付（10/13）のまま、タイトルだけ「すべて」で変更
        $this->actingInFamily()->putJson(route('calendar.events.update', $override->id), $this->payload([
            'title' => 'タイトルだけ', 'start_date' => '2026-10-13', 'end_date' => '2026-10-13', 'rrule' => 'FREQ=WEEKLY;BYDAY=MO', 'scope' => 'all',
        ]))->assertOk();

        $this->assertSame('2026-10-05', $event->fresh()->start_date->toDateString());
        $this->assertSame(1, $event->overrides()->count());
    }

    public function test_update_all_keeps_exceptions_when_rule_differs_only_in_notation(): void
    {
        $event = $this->recurring('FREQ=WEEKLY;INTERVAL=1;BYDAY=MO;WKST=MO');
        $event->exdates()->create(['date' => '2026-10-12']);

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload([
            'title' => '表記だけ違う', 'rrule' => 'FREQ=WEEKLY;BYDAY=MO', 'scope' => 'all', 'occurrence_date' => '2026-10-05',
        ]))->assertOk();

        $this->assertSame(1, $event->exdates()->count());
    }

    public function test_update_following_keeps_the_total_count(): void
    {
        $event = $this->recurring('FREQ=DAILY;COUNT=10', '2026-10-01');

        $this->actingInFamily()->putJson(route('calendar.events.update', $event->id), $this->payload([
            'title' => '後半', 'start_date' => '2026-10-06', 'end_date' => '2026-10-06', 'rrule' => 'FREQ=DAILY;COUNT=10',
            'scope' => 'following', 'occurrence_date' => '2026-10-06',
        ]))->assertOk();

        $this->assertCount(10, $this->events('2026-10-01', '2026-10-31'));
        $this->assertSame('2026-10-10', collect($this->events('2026-10-01', '2026-10-31'))->last()['startDate']);
    }

    public function test_finished_series_are_not_loaded_but_ongoing_ones_are(): void
    {
        $finished = $this->recurring('FREQ=DAILY;COUNT=3', '2026-01-01');
        $ongoing = $this->recurring('FREQ=MONTHLY', '2026-01-10');

        $this->assertSame('2026-01-03', $finished->fresh()->recurrence_end_date->toDateString());
        $this->assertNull($ongoing->fresh()->recurrence_end_date);
        $this->assertSame(['2026-10-10'], collect($this->events('2026-10-01', '2026-10-31'))->pluck('startDate')->all());
    }

    public function test_end_time_must_be_after_start_time_on_the_same_day(): void
    {
        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload([
            'all_day' => false, 'start_time' => '18:00', 'end_time' => '09:00',
        ]))->assertJsonValidationErrors('end_time');

        // 翌日にまたがる予定は終了時刻が早くてもよい
        $this->actingInFamily()->postJson(route('calendar.events.store'), $this->payload([
            'all_day' => false, 'start_time' => '18:00', 'end_time' => '09:00', 'end_date' => '2026-10-06',
        ]))->assertCreated();
    }
}

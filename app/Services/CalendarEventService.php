<?php

namespace App\Services;

use App\Dtos\Calendar\CalendarEventRequest;
use App\Dtos\Calendar\CalendarEventResult;
use App\Models\CalendarEvent;
use App\Models\CalendarEventExdate;
use App\Models\Family;
use App\Models\User;
use App\Models\VirtualUser;
use App\Support\CalendarRecurrence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * カレンダーの予定（家族で共有）の取得・作成・更新・削除。
 *
 * 繰り返し予定の変更・削除の範囲（scope）:
 * - this:      この回だけ（変更は上書き予定を作る / 削除は除外日を追加する）
 * - following: この回以降（繰り返し元を前日で終わらせ、変更ならこの回から新しい予定を作る）
 * - all:       すべて（繰り返し元を変更・削除する）
 */
class CalendarEventService
{

    /**
     * 期間内（両端を含む）の予定を、繰り返しを展開して返す（誕生日を含む）
     *
     * @return CalendarEventResult[]
     */
    public function occurrences(Family $family, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $events = CalendarEvent::with(['participants', 'exdates', 'recurringEvent'])
            ->where('family_id', $family->id)
            ->where('start_date', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->where(function ($single) use ($from) {
                    $single->whereNull('rrule')->where('end_date', '>=', $from->toDateString());
                })->orWhere(function ($recurring) use ($from) {
                    // 終わりのない繰り返し、または最後の回（複数日なら最終日）が期間に届くもの
                    $recurring->whereNotNull('rrule')->where(function ($end) use ($from) {
                        $end->whereNull('recurrence_end_date')
                            ->orWhereRaw('DATE_ADD(recurrence_end_date, INTERVAL DATEDIFF(end_date, start_date) DAY) >= ?', [$from->toDateString()]);
                    });
                });
            })
            ->get();

        $parents = $events->filter(fn (CalendarEvent $e) => $e->isRecurring());

        // この回だけ変更した日（繰り返し元の展開から外す）。期間外の上書き予定も見る
        $overriddenDates = CalendarEvent::whereIn('recurring_event_id', $parents->pluck('id'))
            ->get(['recurring_event_id', 'original_date'])
            ->groupBy('recurring_event_id')
            ->map(fn ($rows) => $rows->map(fn ($r) => $r->original_date->toDateString())->all());

        $results = [];

        foreach ($events as $event) {
            if (!$event->isRecurring()) {
                $results[] = $this->toResult(
                    $event,
                    $event->original_date?->toDateString() ?? $event->start_date->toDateString(),
                    $event->start_date,
                );

                continue;
            }

            $exdates = array_merge(
                $event->exdates->map(fn (CalendarEventExdate $x) => $x->date->toDateString())->all(),
                $overriddenDates->get($event->id, []),
            );

            // 複数日の予定は、期間より前に始まって期間内に続く回も含める
            $dates = CalendarRecurrence::occurrenceDates(
                $event->rrule,
                $event->start_date,
                $from->copy()->subDays($event->durationDays()),
                $to,
                $exdates,
            );

            foreach ($dates as $date) {
                $results[] = $this->toResult($event, $date, Carbon::parse($date));
            }
        }

        $results = array_merge($results, $this->birthdays($family, $from, $to));

        usort($results, fn (CalendarEventResult $a, CalendarEventResult $b) => [
            $a->start_date, !$a->all_day, $a->start_time ?? '', $a->title,
        ] <=> [
            $b->start_date, !$b->all_day, $b->start_time ?? '', $b->title,
        ]);

        return $results;
    }

    public function create(Family $family, User $user, CalendarEventRequest $data): CalendarEvent
    {
        return DB::transaction(function () use ($family, $user, $data) {
            $event = CalendarEvent::create([
                ...$this->attributes($data),
                'family_id' => $family->id,
                'created_by' => $user->id,
                'rrule' => $data->rrule,
            ]);

            $this->syncParticipants($family, $event, $data->participant_ids);

            return $event;
        });
    }

    /**
     * @throws ValidationException
     */
    public function update(Family $family, CalendarEvent $event, CalendarEventRequest $data): void
    {
        DB::transaction(function () use ($family, $event, $data) {
            // 単発の予定
            if (!$event->isRecurring() && !$event->isOverride()) {
                $event->update([...$this->attributes($data), 'rrule' => $data->rrule]);
                $this->syncParticipants($family, $event, $data->participant_ids);

                return;
            }

            $scope = $data->scope ?? 'this';

            // この回だけ変更した予定
            if ($event->isOverride()) {
                if ($scope === 'this') {
                    $event->update($this->attributes($data));
                    $this->syncParticipants($family, $event, $data->participant_ids);

                    return;
                }

                // 日付のずれは、画面に表示していた（上書き予定の）日付を基準に計算する
                $this->updateSeries($family, $event->recurringEvent, $event->original_date, $event->start_date, $scope, $data);

                return;
            }

            $occurrence = $this->occurrenceDate($event, $data->occurrence_date);
            $this->updateSeries($family, $event, $occurrence, $occurrence, $scope, $data);
        });
    }

    public function delete(CalendarEvent $event, ?string $scope, ?string $occurrenceDate): void
    {
        DB::transaction(function () use ($event, $scope, $occurrenceDate) {
            $scope ??= 'this';

            if (!$event->isRecurring() && !$event->isOverride()) {
                $event->delete();

                return;
            }

            if ($event->isOverride()) {
                $parent = $event->recurringEvent;
                $original = $event->original_date;

                if ($scope === 'this') {
                    $this->addExdate($parent, $original);
                    $event->forceDelete();

                    return;
                }

                $this->deleteSeries($parent, $original, $scope);

                return;
            }

            $this->deleteSeries($event, $this->occurrenceDate($event, $occurrenceDate), $scope);
        });
    }

    /**
     * 繰り返し予定のある回（$occurrence）を起点に、範囲を指定して変更する
     *
     * @param  Carbon  $occurrence  その回の本来の発生日
     * @param  Carbon  $shownStart  その回として画面に表示していた開始日（この回だけ日付を動かした上書き予定ではずれる）
     */
    private function updateSeries(Family $family, CalendarEvent $parent, Carbon $occurrence, Carbon $shownStart, string $scope, CalendarEventRequest $data): void
    {
        if ($scope === 'this') {
            $override = CalendarEvent::updateOrCreate(
                ['recurring_event_id' => $parent->id, 'original_date' => $occurrence->toDateString()],
                [...$this->attributes($data), 'family_id' => $parent->family_id, 'created_by' => $parent->created_by, 'rrule' => null],
            );
            $this->syncParticipants($family, $override, $data->participant_ids);

            return;
        }

        // 最初の回からの「これ以降」は「すべて」と同じ
        if ($scope === 'following' && $occurrence->gt($parent->start_date)) {
            $rrule = $this->remainingRule($parent, $occurrence, $data->rrule);
            $this->endSeriesBefore($parent, $occurrence);

            $next = CalendarEvent::create([
                ...$this->attributes($data),
                'family_id' => $parent->family_id,
                'created_by' => $parent->created_by,
                'rrule' => $rrule,
            ]);
            $this->syncParticipants($family, $next, $data->participant_ids);

            return;
        }

        // すべて: この回で変えた日付の差分だけ、繰り返しの開始日をずらす
        $shift = (int) $shownStart->diffInDays(Carbon::parse($data->start_date), false);
        $length = (int) Carbon::parse($data->start_date)->diffInDays(Carbon::parse($data->end_date));
        $start = $parent->start_date->copy()->addDays($shift);

        $ruleChanged = CalendarRecurrence::normalize($data->rrule) !== CalendarRecurrence::normalize($parent->rrule) || $shift !== 0;

        $parent->update([
            ...$this->attributes($data),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays($length)->toDateString(),
            'rrule' => $data->rrule,
        ]);
        $this->syncParticipants($family, $parent, $data->participant_ids);

        // 繰り返し方が変わると、この回だけの変更・削除が別の日を指してしまうため取り消す
        if ($ruleChanged || $data->rrule === null) {
            $parent->overrides()->get()->each->forceDelete();
            $parent->exdates()->delete();
        }
    }

    private function deleteSeries(CalendarEvent $parent, Carbon $occurrence, string $scope): void
    {
        if ($scope === 'this') {
            $this->addExdate($parent, $occurrence);
            $parent->overrides()->whereDate('original_date', $occurrence->toDateString())->get()->each->forceDelete();

            return;
        }

        if ($scope === 'following' && $occurrence->gt($parent->start_date)) {
            $this->endSeriesBefore($parent, $occurrence);

            return;
        }

        $parent->overrides()->get()->each->forceDelete();
        $parent->delete();
    }

    /**
     * 「これ以降」で分けた後半のルール。回数指定（COUNT）を引き継ぐ場合は、前半で済んだ回数を引く
     * （画面は元の COUNT をそのまま送るため、そのまま使うと合計回数が増える）
     */
    private function remainingRule(CalendarEvent $parent, Carbon $occurrence, ?string $rrule): ?string
    {
        $count = $rrule ? CalendarRecurrence::count($rrule) : null;

        if ($count === null || $count !== CalendarRecurrence::count($parent->rrule)) {
            return $rrule;
        }

        $done = CalendarRecurrence::countBefore($parent->rrule, $parent->start_date, $occurrence);

        return CalendarRecurrence::withCount($rrule, max(1, $count - $done));
    }

    /**
     * 繰り返しを指定した回の前日で終わらせ、それ以降の上書き予定・除外日を取り消す
     */
    private function endSeriesBefore(CalendarEvent $parent, Carbon $occurrence): void
    {
        $parent->update(['rrule' => CalendarRecurrence::withUntil($parent->rrule, $occurrence->copy()->subDay())]);

        $parent->overrides()->whereDate('original_date', '>=', $occurrence->toDateString())->get()->each->forceDelete();
        $parent->exdates()->whereDate('date', '>=', $occurrence->toDateString())->delete();
    }

    private function addExdate(CalendarEvent $parent, Carbon $date): void
    {
        CalendarEventExdate::firstOrCreate([
            'calendar_event_id' => $parent->id,
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * 変更・削除の対象の回。指定がなければ繰り返しの最初の回
     *
     * @throws ValidationException
     */
    private function occurrenceDate(CalendarEvent $parent, ?string $date): Carbon
    {
        if ($date === null) {
            return $parent->start_date->copy();
        }

        $occurrence = Carbon::parse($date)->startOfDay();
        $dates = CalendarRecurrence::occurrenceDates($parent->rrule, $parent->start_date, $occurrence, $occurrence);

        if ($dates === []) {
            throw ValidationException::withMessages(['occurrence_date' => '指定した日はこの予定の繰り返しに含まれません。']);
        }

        return $occurrence;
    }

    /**
     * 参加者を入れ替える。現在の家族のメンバー・仮想ユーザー以外は受け付けない
     *
     * @param  string[]  $ids
     *
     * @throws ValidationException
     */
    private function syncParticipants(Family $family, CalendarEvent $event, array $ids): void
    {
        $userIds = $family->members()->whereIn('users.id', $ids)->pluck('users.id')->all();
        $virtualIds = VirtualUser::where('family_id', $family->id)->whereIn('id', $ids)->pluck('id')->all();

        if (count($userIds) + count($virtualIds) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['participant_ids' => '参加者には家族のメンバーを選んでください。']);
        }

        $event->participants()->delete();

        foreach ($ids as $id) {
            $event->participants()->create([
                'participant_type' => in_array($id, $userIds, true) ? User::class : VirtualUser::class,
                'participant_id' => $id,
            ]);
        }
    }

    /**
     * 予定の内容（繰り返しのルール・家族・作成者以外）
     *
     * @return array<string, mixed>
     */
    private function attributes(CalendarEventRequest $data): array
    {
        return [
            'title' => trim($data->title),
            'memo' => $data->memo !== null && trim($data->memo) !== '' ? $data->memo : null,
            'label_id' => $data->label_id,
            'all_day' => $data->all_day,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'start_time' => $data->all_day ? null : $data->start_time,
            'end_time' => $data->all_day ? null : $data->end_time,
        ];
    }

    /**
     * 予定（繰り返しはその回）を返却用に変換する
     */
    private function toResult(CalendarEvent $event, string $occurrenceDate, Carbon $start): CalendarEventResult
    {
        $series = $event->isOverride() ? $event->recurringEvent : $event;

        return new CalendarEventResult(
            id: $event->id,
            occurrence_date: $occurrenceDate,
            title: $event->title,
            memo: $event->memo,
            all_day: $event->all_day,
            start_date: $start->toDateString(),
            end_date: $start->copy()->addDays($event->durationDays())->toDateString(),
            start_time: $event->start_time ? substr($event->start_time, 0, 5) : null,
            end_time: $event->end_time ? substr($event->end_time, 0, 5) : null,
            label_id: $event->label_id,
            participant_ids: $event->participants->pluck('participant_id')->all(),
            rrule: $series?->rrule,
            is_recurring: $event->isRecurring() || $event->isOverride(),
        );
    }

    /**
     * 家族のメンバーの誕生日（期間内）
     *
     * @return CalendarEventResult[]
     */
    private function birthdays(Family $family, Carbon $from, Carbon $to): array
    {
        /** @var Collection<int, User> $members */
        $members = $family->members()->whereNotNull('birthday')->get(['users.id', 'users.name', 'users.birthday']);
        $results = [];

        foreach ($members as $member) {
            $birthday = Carbon::parse($member->birthday);

            for ($year = $from->year; $year <= $to->year; $year++) {
                // 2/29 生まれは、うるう年以外は 2/28 に表示する
                $day = min($birthday->day, Carbon::create($year, $birthday->month, 1)->daysInMonth);
                $date = Carbon::create($year, $birthday->month, $day)->startOfDay();

                if ($date->lt($from) || $date->gt($to) || $date->lt($birthday->copy()->startOfDay())) {
                    continue;
                }

                $results[] = new CalendarEventResult(
                    id: "birthday:{$member->id}:{$year}",
                    occurrence_date: $date->toDateString(),
                    title: "{$member->name}の誕生日",
                    memo: null,
                    all_day: true,
                    start_date: $date->toDateString(),
                    end_date: $date->toDateString(),
                    start_time: null,
                    end_time: null,
                    label_id: null,
                    participant_ids: [$member->id],
                    rrule: null,
                    is_recurring: false,
                    is_birthday: true,
                );
            }
        }

        return $results;
    }
}

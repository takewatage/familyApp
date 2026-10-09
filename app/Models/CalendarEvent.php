<?php

namespace App\Models;

use App\Support\CalendarRecurrence;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * カレンダーの予定（家族で共有）。
 *
 * - 繰り返し予定: rrule を持つ（DTSTART は start_date）。除外日は exdates
 * - この回だけ変更した予定: recurring_event_id（繰り返し元）と original_date（本来の発生日）を持つ別の行
 * - 日時は家族の現地時刻（Asia/Tokyo）の日付＋時刻で保存する（タイムゾーン変換しない）
 *
 * @property string $id
 * @property string $family_id
 * @property string|null $created_by
 * @property string $title
 * @property string|null $memo
 * @property string|null $label_id
 * @property bool $all_day
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property string|null $start_time
 * @property string|null $end_time
 * @property string|null $rrule
 * @property Carbon|null $recurrence_end_date
 * @property string|null $recurring_event_id
 * @property Carbon|null $original_date
 * @property-read \App\Models\Family $family
 * @property-read \App\Models\CalendarLabel|null $label
 * @property-read \App\Models\CalendarEvent|null $recurringEvent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CalendarEvent> $overrides
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CalendarEventExdate> $exdates
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CalendarEventParticipant> $participants
 */
class CalendarEvent extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'family_id',
        'created_by',
        'title',
        'memo',
        'label_id',
        'all_day',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'rrule',
        'recurrence_end_date',
        'recurring_event_id',
        'original_date',
    ];

    protected $casts = [
        'all_day' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'original_date' => 'date',
        'recurrence_end_date' => 'date',
    ];

    protected static function booted(): void
    {
        // 取得時に終わった繰り返しを読み込まないよう、最後の発生日を保存しておく
        static::saving(function (CalendarEvent $event) {
            $event->recurrence_end_date = $event->rrule
                ? CalendarRecurrence::lastDate($event->rrule, $event->start_date)
                : null;
        });
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(CalendarLabel::class);
    }

    /** この回だけ変更した予定の繰り返し元 */
    public function recurringEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recurring_event_id');
    }

    /** この回だけ変更した予定（繰り返し元から見た上書き予定） */
    public function overrides(): HasMany
    {
        return $this->hasMany(self::class, 'recurring_event_id');
    }

    public function exdates(): HasMany
    {
        return $this->hasMany(CalendarEventExdate::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CalendarEventParticipant::class);
    }

    public function isRecurring(): bool
    {
        return $this->rrule !== null;
    }

    public function isOverride(): bool
    {
        return $this->recurring_event_id !== null;
    }

    /** 開始日から終了日までの日数（1日の予定なら 0） */
    public function durationDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }
}

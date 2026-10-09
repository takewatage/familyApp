<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 繰り返し予定の除外日（この回だけ削除した発生日）
 *
 * @property int $id
 * @property string $calendar_event_id
 * @property Carbon $date
 */
class CalendarEventExdate extends Model
{
    protected $fillable = ['calendar_event_id', 'date'];

    protected $casts = [
        'date' => 'date',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }
}

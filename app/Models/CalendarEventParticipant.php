<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 予定の参加者（User / VirtualUser）
 *
 * @property int $id
 * @property string $calendar_event_id
 * @property string $participant_type
 * @property string $participant_id
 * @property-read \App\Models\User|\App\Models\VirtualUser $participant
 */
class CalendarEventParticipant extends Model
{
    protected $fillable = ['calendar_event_id', 'participant_type', 'participant_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function participant(): MorphTo
    {
        return $this->morphTo();
    }
}

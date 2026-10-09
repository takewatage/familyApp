<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 予定のラベル（名前＋カラー）。家族で共有する。
 *
 * @property string $id
 * @property string $family_id
 * @property string $name
 * @property string $color
 * @property int $sort
 * @property-read \App\Models\Family $family
 */
class CalendarLabel extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['family_id', 'name', 'color', 'sort'];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}

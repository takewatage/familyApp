<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $family_id
 * @property string|null $category_id
 * @property int $threshold_percent
 * @property bool $is_enabled
 * @property-read \App\Models\Family $family
 * @property-read \App\Models\Category|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AlertNotification> $notifications
 */
class BudgetAlert extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'family_id',
        'category_id',
        'threshold_percent',
        'is_enabled',
    ];

    protected $casts = [
        'threshold_percent' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class, 'alert_id');
    }
}

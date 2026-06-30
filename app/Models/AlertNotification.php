<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $alert_id
 * @property string $year_month
 * @property \Illuminate\Support\Carbon $triggered_at
 * @property string $actual_percent
 * @property-read \App\Models\BudgetAlert $alert
 */
class AlertNotification extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'year_month',
        'triggered_at',
        'actual_percent',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'actual_percent' => 'decimal:2',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(BudgetAlert::class, 'alert_id');
    }
}

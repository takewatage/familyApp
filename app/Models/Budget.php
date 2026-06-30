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
 * @property string $year_month
 * @property string $total_income
 * @property string $saving_target
 * @property-read \App\Models\Family $family
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BudgetCategory> $budgetCategories
 */
class Budget extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'family_id',
        'year_month',
        'total_income',
        'saving_target',
    ];

    protected $casts = [
        'total_income' => 'decimal:2',
        'saving_target' => 'decimal:2',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function budgetCategories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class);
    }
}

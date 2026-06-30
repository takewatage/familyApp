<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $family_id
 * @property string $name
 * @property string|null $default_category_id
 * @property int $usage_count
 * @property-read \App\Models\Family $family
 * @property-read \App\Models\Category|null $defaultCategory
 */
class Shop extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'family_id',
        'name',
        'default_category_id',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    /**
     * 家族の店舗を候補表示順（利用回数降順 → 名前昇順）で取得するスコープ。
     * 店舗一覧・検索・支出フォームの候補で共通利用する。
     */
    public function scopeForFamilyOrdered(Builder $query, ?string $familyId): Builder
    {
        return $query->where('family_id', $familyId)
            ->orderByDesc('usage_count')
            ->orderBy('name');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function defaultCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'default_category_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

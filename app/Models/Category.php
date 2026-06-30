<?php

namespace App\Models;

use App\Models\Concerns\HasFamilyVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $family_id
 * @property string|null $parent_id
 * @property string $name
 * @property string|null $icon
 * @property string $color
 * @property int $sort_order
 * @property bool $is_system
 * @property bool $is_active
 * @property-read \App\Models\Family|null $family
 * @property-read \App\Models\Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category> $children
 */
class Category extends Model
{
    use HasFactory, HasFamilyVisibility, HasUuids;

    protected $fillable = [
        'family_id',
        'parent_id',
        'name',
        'icon',
        'color',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class, 'default_category_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}

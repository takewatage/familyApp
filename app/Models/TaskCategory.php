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
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Family $family
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereFamilyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TaskCategory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'family_id', 'name', 'sort'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'category_id');
    }
}

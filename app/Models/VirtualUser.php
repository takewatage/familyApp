<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 * @property string $family_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Family $family
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\File> $files
 * @mixin \Eloquent
 */
class VirtualUser extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = ['family_id', 'name'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function getAvatarAttribute(): ?File
    {
        return $this->files()->where('collection', 'avatar')->first();
    }
}

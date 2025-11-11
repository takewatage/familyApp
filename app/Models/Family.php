<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Family extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = ['id', 'name', 'code', 'owner_id', 'max_members', 'settings'];

    protected $casts = [
        'settings' => 'array',
        'max_members' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($family) {
            if (empty($family->code)) {
                $family->code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}

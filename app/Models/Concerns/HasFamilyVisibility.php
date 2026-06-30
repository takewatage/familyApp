<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * 「システム既定（family_id = null）または指定家族」の可視性スコープを提供する。
 * カテゴリー・支払い方法のように、全家族共通の既定値と家族固有の値が混在するモデルで使う。
 */
trait HasFamilyVisibility
{
    public function scopeVisibleTo(Builder $query, ?string $familyId): Builder
    {
        return $query->where(function (Builder $q) use ($familyId) {
            $q->whereNull('family_id');

            if ($familyId) {
                $q->orWhere('family_id', $familyId);
            }
        });
    }
}

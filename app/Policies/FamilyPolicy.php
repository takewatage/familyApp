<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    /**
     * 家族設定の変更（オーナーのみ）
     */
    public function update(User $user, Family $family): bool
    {
        return $family->owner_id === $user->id;
    }

    /**
     * 家族コードの再生成（オーナーのみ）
     */
    public function regenerateCode(User $user, Family $family): bool
    {
        return $family->owner_id === $user->id;
    }

    /**
     * メンバーの除名（オーナーのみ）
     */
    public function removeMember(User $user, Family $family): bool
    {
        return $family->owner_id === $user->id;
    }
}

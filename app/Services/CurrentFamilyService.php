<?php

namespace App\Services;

use App\Models\Family;
use Illuminate\Support\Facades\Session;

class CurrentFamilyService
{
    private const SESSION_KEY = 'current_family_id';

    /**
     * 現在の家族IDを設定
     */
    public function setCurrentFamily(string $familyId): void
    {
        Session::put(self::SESSION_KEY, $familyId);
    }

    /**
     * 現在の家族IDを取得
     */
    public function getCurrentFamilyId(): ?string
    {
        return Session::get(self::SESSION_KEY);
    }

    /**
     * 現在の家族モデルを取得
     */
    public function getCurrentFamily(): ?Family
    {
        $familyId = $this->getCurrentFamilyId();

        if (!$familyId) {
            return null;
        }

        return Family::find($familyId);
    }

    /**
     * 現在の家族をクリア
     */
    public function clearCurrentFamily(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * ユーザーが指定された家族に所属しているかチェック
     */
    public function userBelongsToFamily(string $userId, string $familyId): bool
    {
        return \DB::table('family_user')
            ->where('user_id', $userId)
            ->where('family_id', $familyId)
            ->exists();
    }
}

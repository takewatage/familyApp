<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CurrentFamilyService
{
    private const SESSION_KEY = 'current_family_id';

    /** 1 リクエスト内で同じ家族を何度も DB から読まないためのキャッシュ（リクエスト属性に保持） */
    private const REQUEST_CACHE_KEY = 'current_family';

    /**
     * 現在の家族IDを設定
     *
     * セッションに保存すると同時に、ログイン中ユーザーの「最後に選択していた家族」も更新する。
     */
    public function setCurrentFamily(string $familyId): void
    {
        Session::put(self::SESSION_KEY, $familyId);

        $user = Auth::user();

        if ($user instanceof User && $user->last_family_id !== $familyId) {
            $user->forceFill(['last_family_id' => $familyId])->save();
        }
    }

    /**
     * ユーザーの所属家族から現在の家族を決定し、セッションに保存する
     *
     * ログイン直後・新規登録直後に呼び出す。所属家族がない場合はクリアする。
     */
    public function resolveAndSetForUser(User $user): ?Family
    {
        $family = $this->resolveForUser($user);

        if (!$family) {
            $this->clearCurrentFamily();

            return null;
        }

        $this->setCurrentFamily($family->id);

        return $family;
    }

    /**
     * ユーザーの現在の家族を決定する
     *
     * 1. 最後に選択していた家族（所属が継続している場合のみ）
     * 2. フォールバック: 参加日時が最も古い家族
     */
    private function resolveForUser(User $user): ?Family
    {
        if ($user->last_family_id) {
            $lastFamily = $user->families()->where('families.id', $user->last_family_id)->first();

            if ($lastFamily) {
                return $lastFamily;
            }
        }

        return $user->families()->orderBy('family_user.created_at')->first();
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

        $cached = request()->attributes->get(self::REQUEST_CACHE_KEY);

        if ($cached instanceof Family && $cached->id === $familyId) {
            return $cached;
        }

        $family = Family::find($familyId);
        request()->attributes->set(self::REQUEST_CACHE_KEY, $family);

        return $family;
    }

    /**
     * 現在の家族をクリア
     */
    public function clearCurrentFamily(): void
    {
        Session::forget(self::SESSION_KEY);
        request()->attributes->remove(self::REQUEST_CACHE_KEY);
    }

    /**
     * ユーザーが指定された家族に所属しているかチェック
     */
    public function userBelongsToFamily(string $userId, string $familyId): bool
    {
        return \DB::table('family_user')->where('user_id', $userId)->where('family_id', $familyId)->exists();
    }
}

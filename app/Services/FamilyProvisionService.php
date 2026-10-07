<?php

namespace App\Services;

use App\Exceptions\InvalidInviteException;
use App\Models\Family;
use App\Models\User;

/**
 * 新規ユーザーの家族への所属を扱う
 *
 * 新規登録は招待経由に限定する（知らない人がアカウントを作れないようにするため）。
 * 招待URL（/join/{code}）アクセス時にセッションへ保存された招待情報を元に判定する。
 */
class FamilyProvisionService
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    /**
     * セッションの招待情報から、新規登録で参加できる家族を取得する
     *
     * 招待が無効・期限切れ・定員到達の場合は、招待情報をセッションから破棄したうえで例外を投げる。
     *
     * @throws InvalidInviteException
     */
    public function requireValidInviteFamily(): Family
    {
        $code = session('invite_family_code');

        if (!$code) {
            throw new InvalidInviteException('新規登録は家族からの招待リンクからのみ行えます。');
        }

        $family = Family::where('code', $code)->first();

        if (!$family || ($family->code_expires_at && $family->code_expires_at->isPast())) {
            $this->forgetInvite();

            throw new InvalidInviteException('招待リンクが無効または期限切れです。家族に新しい招待リンクを発行してもらってください。');
        }

        if ($family->members()->count() >= $family->max_members) {
            $this->forgetInvite();

            throw new InvalidInviteException('招待先の家族が定員に達しているため登録できません。');
        }

        return $family;
    }

    /**
     * 新規登録できる有効な招待がセッションにあるか
     */
    public function hasValidInvite(): bool
    {
        try {
            $this->requireValidInviteFamily();

            return true;
        } catch (InvalidInviteException) {
            return false;
        }
    }

    /**
     * 新規ユーザーを招待先の家族へ参加させ、現在の家族に設定する
     */
    public function joinInvitedFamily(User $user, Family $family): Family
    {
        $role = in_array(session('invite_role'), ['parent', 'child', 'guest'], true)
            ? session('invite_role')
            : 'guest';

        if (!$family->members()->where('users.id', $user->id)->exists()) {
            $family->members()->attach($user->id, ['role' => $role]);
        }

        $this->forgetInvite();

        $this->currentFamilyService->setCurrentFamily($family->id);

        return $family;
    }

    private function forgetInvite(): void
    {
        session()->forget(['invite_family_code', 'invite_role']);
    }
}

<?php

namespace App\Services;

use App\Models\Family;
use App\Models\User;

class FamilyProvisionService
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    /**
     * 新規ユーザーを家族に所属させる
     *
     * 招待セッションがあれば招待先の家族へ参加させ、なければ本人の家族を新規作成する。
     * 家族未所属のユーザーを作らないことで、既存機能（ホーム・タスク・家計簿）がそのまま利用できる。
     */
    public function provisionForNewUser(User $user): Family
    {
        $inviteFamily = $this->pullValidInviteFamily();

        if ($inviteFamily) {
            $family = $this->joinInvitedFamily($user, $inviteFamily);
        } else {
            $family = $this->createOwnFamily($user);
        }

        $this->currentFamilyService->setCurrentFamily($family->id);

        return $family;
    }

    /**
     * 招待セッションから有効な家族を取得する（取得できたかに関わらずセッションは破棄する）
     */
    private function pullValidInviteFamily(): ?Family
    {
        $code = session('invite_family_code');

        if (!$code) {
            return null;
        }

        $family = Family::where('code', $code)->first();

        if (!$family || ($family->code_expires_at && $family->code_expires_at->isPast())) {
            session()->forget(['invite_family_code', 'invite_role']);

            return null;
        }

        return $family;
    }

    /**
     * 招待先の家族へ参加させる
     */
    private function joinInvitedFamily(User $user, Family $family): Family
    {
        $role = in_array(session('invite_role'), ['parent', 'child', 'guest'], true)
            ? session('invite_role')
            : 'guest';

        if (!$family->members()->where('users.id', $user->id)->exists()) {
            $family->members()->attach($user->id, ['role' => $role]);
        }

        session()->forget(['invite_family_code', 'invite_role']);

        return $family;
    }

    /**
     * 本人をオーナーとする家族を新規作成する
     */
    private function createOwnFamily(User $user): Family
    {
        // 家族コード（code）は Family::creating フックで自動採番される
        $family = Family::create([
            'name' => "{$user->name}の家族",
            'owner_id' => $user->id,
        ]);

        $family->members()->attach($user->id, ['role' => 'owner']);

        return $family;
    }
}

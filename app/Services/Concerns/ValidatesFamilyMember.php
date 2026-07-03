<?php

namespace App\Services\Concerns;

use App\Models\User;
use App\Models\VirtualUser;
use Illuminate\Validation\ValidationException;

/**
 * 担当者（polymorphic member: User / VirtualUser）が当該家族に所属することを保証する。
 *
 * バリデーションの exists ルールはグローバル存在のみを見るため、他家族のメンバー ID を
 * 含むリクエストが素通りしてしまう。支出・繰り返し支出のように担当者を持つモデルで、
 * 一覧描画時に他家族の担当者名が露出する越境漏洩を 422 として塞ぐ。
 */
trait ValidatesFamilyMember
{
    /**
     * 担当者が指定されている場合、その担当者が当該家族に所属することを検証する。
     * 未指定（member_type / member_id がともに null）は何もしない。
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function assertMemberInFamilyScope(string $familyId, array $attributes): void
    {
        $memberType = $attributes['member_type'] ?? null;
        $memberId = $attributes['member_id'] ?? null;

        if ($memberType !== null && $memberId !== null
            && ! $this->memberBelongsToFamily($familyId, $memberType, $memberId)) {
            throw ValidationException::withMessages(['member_id' => '担当者が選択できません。']);
        }
    }

    /**
     * 担当者（実ユーザー / 仮想ユーザー）が当該家族に所属しているか。
     */
    protected function memberBelongsToFamily(string $familyId, string $memberType, string $memberId): bool
    {
        return match ($memberType) {
            User::class => User::whereKey($memberId)
                ->whereHas('families', fn ($q) => $q->whereKey($familyId))
                ->exists(),
            VirtualUser::class => VirtualUser::whereKey($memberId)
                ->where('family_id', $familyId)
                ->exists(),
            default => false,
        };
    }
}

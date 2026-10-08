<?php

namespace App\Services;

use App\Dtos\Family\FamilyMemberData;
use App\Dtos\Model\FileData;
use App\Dtos\Model\VirtualUserData;
use App\Models\Family;
use App\Models\User;
use App\Models\VirtualUser;

/**
 * 家族のメンバー（ログインユーザー）と仮想ユーザーを、アバター付きの DTO に変換する
 *
 * ホーム・メンバー管理・カレンダーなど、家族メンバーを一覧表示する画面で共通して使う。
 * 並び順は家族への参加順（メンバー）・作成順（仮想ユーザー）で固定する。
 */
class FamilyMemberService
{
    /**
     * @return FamilyMemberData[]
     */
    public function members(Family $family): array
    {
        $this->load($family);

        return $family->members->map(fn (User $user) => new FamilyMemberData(
            id: $user->id,
            name: $user->name,
            role: $user->pivot->role,
            avatar: $this->avatarOf($user),
        ))->values()->all();
    }

    /**
     * @return VirtualUserData[]
     */
    public function virtualUsers(Family $family): array
    {
        $this->load($family);

        return $family->virtualUsers->map(fn (VirtualUser $vu) => new VirtualUserData(
            id: $vu->id,
            family_id: $vu->family_id,
            name: $vu->name,
            created_at: $vu->created_at?->toIso8601String(),
            updated_at: $vu->updated_at?->toIso8601String(),
            avatar: $this->avatarOf($vu),
        ))->values()->all();
    }

    /**
     * メンバー・仮想ユーザーとアバター画像を並び順付きで読み込む（読み込み済みなら何もしない）
     */
    private function load(Family $family): void
    {
        if (! $family->relationLoaded('members')) {
            $family->load([
                'members' => fn ($q) => $q->orderBy('family_user.created_at')->orderBy('users.id'),
                'members.files',
            ]);
        }

        if (! $family->relationLoaded('virtualUsers')) {
            $family->load([
                'virtualUsers' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
                'virtualUsers.files',
            ]);
        }
    }

    private function avatarOf(User|VirtualUser $owner): ?FileData
    {
        $file = $owner->files->firstWhere('collection', 'avatar');

        return $file ? FileData::from($file->toArray()) : null;
    }
}

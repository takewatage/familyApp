<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * 家計簿リクエスト DTO で共通利用するカテゴリーの family スコープ validation ルール。
 *
 * 「システム既定（family_id=null）または当該家族」のカテゴリーに限定する Rule::exists を 1 箇所に集約する。
 * 各 Request DTO でクロージャを複製していた重複を解消する（スコープ規則の変更を 1 箇所で追従できる）。
 */
class BudgetScopeRules
{
    /**
     * 有効（is_active=true）かつ「システム既定 or 家族」のカテゴリー。
     * 選択肢ドロップダウン（activeOptions）と一致し、新規入力で参照するカテゴリーに使う。
     */
    public static function activeCategory(?string $familyId): Exists
    {
        return self::categoryExists($familyId, true);
    }

    /**
     * is_active を問わない「システム既定 or 家族」のカテゴリー。
     * 既存アラート等、後からカテゴリーが無効化されても再保存を妨げないようにする用途で使う。
     */
    public static function familyCategory(?string $familyId): Exists
    {
        return self::categoryExists($familyId, false);
    }

    private static function categoryExists(?string $familyId, bool $requireActive): Exists
    {
        return Rule::exists('categories', 'id')->where(function ($query) use ($familyId, $requireActive) {
            $query->where(function ($scope) use ($familyId) {
                $scope->whereNull('family_id')->orWhere('family_id', $familyId);
            });

            if ($requireActive) {
                $query->where('is_active', true);
            }
        });
    }
}

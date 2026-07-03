<?php

namespace App\Http\Controllers\Concerns;

use App\Dtos\Budget\MemberOptionData;
use App\Dtos\Model\CategoryData;
use App\Dtos\Model\PaymentMethodData;
use App\Dtos\Model\QuickEntryData;
use App\Dtos\Model\ShopData;
use App\Models\Category;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\QuickEntry;
use App\Models\Shop;
use App\Models\User;
use App\Models\VirtualUser;

/**
 * 家計簿の各画面で共通利用する選択肢ロード（カテゴリー / 支払い方法 / 店舗 / クイック入力 / 担当者）。
 * 支出フォーム・クイック入力フォーム等で同一のスコープ・整形を保証するため 1 箇所に集約する。
 */
trait ProvidesBudgetOptions
{
    /**
     * 家族＋システム既定の有効なカテゴリー（並び順）。
     *
     * @return CategoryData[]
     */
    protected function budgetCategoryOptions(?string $familyId): array
    {
        return Category::activeOptions($familyId)
            ->get()
            ->map(fn (Category $c) => CategoryData::from($c))
            ->all();
    }

    /**
     * 家族＋システム既定の有効な支払い方法（並び順）。
     *
     * @return PaymentMethodData[]
     */
    protected function budgetPaymentMethodOptions(?string $familyId): array
    {
        return PaymentMethod::activeOptions($familyId)
            ->get()
            ->map(fn (PaymentMethod $p) => PaymentMethodData::from($p))
            ->all();
    }

    /**
     * 家族の店舗（利用回数降順）。
     *
     * @return ShopData[]
     */
    protected function budgetShopOptions(?string $familyId): array
    {
        if (! $familyId) {
            return [];
        }

        return Shop::forFamilyOrdered($familyId)
            ->get()
            ->map(fn (Shop $s) => ShopData::from($s))
            ->all();
    }

    /**
     * よく使うクイック入力（利用頻度降順）。支出フォームのワンタップ・プリセットに使う。
     *
     * @return QuickEntryData[]
     */
    protected function budgetQuickEntryOptions(?string $familyId): array
    {
        if (! $familyId) {
            return [];
        }

        return QuickEntry::with(['category', 'paymentMethod', 'shop'])
            ->where('family_id', $familyId)
            ->orderByDesc('usage_count')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (QuickEntry $q) => QuickEntryData::fromModel($q))
            ->all();
    }

    /**
     * 支出の担当者選択肢（実ユーザー + 仮想ユーザー）。key = "{member_type}|{member_id}"。
     *
     * @return MemberOptionData[]
     */
    protected function budgetMemberOptions(?string $familyId): array
    {
        if (! $familyId) {
            return [];
        }

        // 呼び出し側が解決済みの familyId をそのまま使う（CurrentFamilyService への再問い合わせを避ける）。
        $family = Family::with(['members', 'virtualUsers'])->find($familyId);

        if (! $family) {
            return [];
        }

        $options = [];

        foreach ($family->members as $user) {
            $options[] = new MemberOptionData(
                key: User::class.'|'.$user->id,
                member_type: User::class,
                member_id: $user->id,
                name: $user->name,
            );
        }

        foreach ($family->virtualUsers as $vu) {
            $options[] = new MemberOptionData(
                key: VirtualUser::class.'|'.$vu->id,
                member_type: VirtualUser::class,
                member_id: $vu->id,
                name: $vu->name,
            );
        }

        return $options;
    }

    /**
     * クエリ `month`（YYYY-MM）を対象年月に解決する。月切替を伴う家計簿画面で共通利用する。
     *
     * 月は 01-12 のみ許可する。範囲外（00・13-99）を通すと Carbon が別月/別年へ桁上がりし、
     * ヘッダー表示と実際の集計月がずれるため正規表現で弾き、不正時は当月へフォールバックする。
     */
    protected function resolveYearMonth(?string $month): string
    {
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $month;
        }

        return now()->format('Y-m');
    }
}

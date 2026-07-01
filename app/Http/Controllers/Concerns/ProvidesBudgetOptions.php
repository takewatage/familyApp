<?php

namespace App\Http\Controllers\Concerns;

use App\Dtos\Model\CategoryData;
use App\Dtos\Model\PaymentMethodData;
use App\Dtos\Model\QuickEntryData;
use App\Dtos\Model\ShopData;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\QuickEntry;
use App\Models\Shop;

/**
 * 家計簿の各画面で共通利用する選択肢ロード（カテゴリー / 支払い方法 / 店舗 / クイック入力）。
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
}

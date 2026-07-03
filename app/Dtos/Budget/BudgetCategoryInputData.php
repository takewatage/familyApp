<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カテゴリー別予算の 1 行分の入力（category_id + 予算額）。
 * StoreBudgetCategoriesRequest の配列要素として使う。
 */
#[TypeScript]
class BudgetCategoryInputData extends Data
{
    public function __construct(
        public string $category_id,
        public string $amount,
    ) {}
}

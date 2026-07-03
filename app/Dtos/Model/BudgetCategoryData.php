<?php

namespace App\Dtos\Model;

use App\Models\BudgetCategory;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カテゴリー別予算の表示用 DTO（当月予算のカテゴリー配分）。
 * 画面は category_id をキーに金額を編集するため、必要最小限の 2 フィールドのみ持つ。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class BudgetCategoryData extends Data
{
    public function __construct(
        public string $category_id,
        public string $amount,
    ) {}

    public static function fromModel(BudgetCategory $budgetCategory): self
    {
        return new self(
            category_id: $budgetCategory->category_id,
            amount: (string) $budgetCategory->amount,
        );
    }
}

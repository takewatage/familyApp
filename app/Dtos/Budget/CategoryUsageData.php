<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カテゴリー別の予算消化状況（F-11）。
 * usage_percent は予算が 0（未設定）の場合 null（消化率を定義できないため）。
 * 金額はすべて decimal(12,2) 文字列で、remaining は予算超過で負値になりうる。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CategoryUsageData extends Data
{
    public function __construct(
        public string $category_id,
        public string $budget_amount,
        public string $actual_amount,
        public string $remaining,
        public ?string $usage_percent,
    ) {}
}

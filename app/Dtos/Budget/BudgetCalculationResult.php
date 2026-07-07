<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 当月の予算計算結果（F-11: 残高・消化率の可視化）。
 *
 * 算出定義（家計簿の集計方針。design.md「未解決事項」の確定版）:
 * - fixed_cost_total（固定費）= 当月に支払日が到来する有効な繰り返し支出の金額合計（生成済みか否かに依らずマスターから算出）
 * - category_budget_total（生活費予算）= カテゴリー別予算（budget_categories）の合計
 * - discretionary（自由に使えるお金）= total_income − fixed_cost_total − category_budget_total − saving_target
 * - possible_saving（貯金可能額）= total_income − total_expense（実支出。生成済み固定費を含む実績ベース）
 * - overall_usage_percent（全体消化率）= total_expense ÷ total_income（収入未設定=null）
 *
 * 金額は decimal(12,2) 文字列。discretionary / possible_saving は負値になりうる（超過・赤字の可視化のため clamp しない）。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class BudgetCalculationResult extends Data
{
    public function __construct(
        public string $year_month,
        public string $total_income,
        public string $total_expense,
        public string $fixed_cost_total,
        public string $category_budget_total,
        public string $saving_target,
        public string $discretionary,
        public string $possible_saving,
        public ?string $overall_usage_percent,
        /** @var CategoryUsageData[] */
        public array $categories,
    ) {}
}

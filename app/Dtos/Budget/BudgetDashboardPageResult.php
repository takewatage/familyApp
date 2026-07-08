<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\CategoryData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 家計簿ダッシュボード（F-19）の表示データ。
 * 当月の予算計算結果（残高・消化率）と固定費リマインダーを 1 画面に集約する。
 * グラフ（F-14〜F-18 / T-10）は未実装のため、消化率は数値＋プログレスバーで表示する。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class BudgetDashboardPageResult extends Data
{
    public function __construct(
        public string $year_month,
        public BudgetCalculationResult $calculation,
        /** @var RecurringReminderData[] */
        public array $reminders,
        /** @var CategoryData[] */
        public array $categories,
    ) {}
}

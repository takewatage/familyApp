<?php

namespace App\Services;

use App\Dtos\Budget\BudgetCalculationResult;
use App\Dtos\Budget\CategoryUsageData;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Support\Carbon;

/**
 * 当月の予算計算（F-11: 残高・消化率・自由に使えるお金・貯金可能額）を担う。
 *
 * すべて family スコープで動作し、family_id は呼び出し側が解決済みで渡す。
 * 金額は decimal(12,2) 文字列のまま bcmath で計算し、浮動小数の丸め誤差を避ける
 * （設計の「金額は decimal を文字列で授受」方針に整合）。消化率は小数第 2 位で丸める。
 */
class BudgetCalculationService
{
    private const SCALE = 2;

    public function __construct(
        private readonly BudgetService $budgetService,
    ) {}

    /**
     * 指定家族・年月（YYYY-MM）の予算計算結果を返す。予算未設定でも実支出から集計する。
     */
    public function calculate(string $familyId, string $yearMonth): BudgetCalculationResult
    {
        $monthStart = Carbon::parse($yearMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // 予算設定（収入・貯金目標・カテゴリー別予算）。未設定月は 0 として扱う。
        $budget = $this->budgetService->findForMonth($familyId, $yearMonth);
        $totalIncome = $budget ? (string) $budget->total_income : '0.00';
        $savingTarget = $budget ? (string) $budget->saving_target : '0.00';

        $budgetByCategory = [];
        $categoryBudgetTotal = '0.00';
        if ($budget) {
            foreach ($budget->budgetCategories as $bc) {
                $budgetByCategory[$bc->category_id] = (string) $bc->amount;
                $categoryBudgetTotal = bcadd($categoryBudgetTotal, (string) $bc->amount, self::SCALE);
            }
        }

        // 当月の実支出をカテゴリー別・全体で集計（生成済み固定費を含む）。
        // DB 非依存かつ decimal 精度を保つため PHP 側で bcadd 集計する。
        $actualByCategory = [];
        $totalExpense = '0.00';
        Expense::where('family_id', $familyId)
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['category_id', 'amount'])
            ->each(function (Expense $expense) use (&$actualByCategory, &$totalExpense) {
                $amount = (string) $expense->amount;
                $actualByCategory[$expense->category_id] = bcadd(
                    $actualByCategory[$expense->category_id] ?? '0',
                    $amount,
                    self::SCALE,
                );
                $totalExpense = bcadd($totalExpense, $amount, self::SCALE);
            });

        // 固定費合計: 当月に支払日が到来する有効な繰り返し支出（生成タイミングに依らずマスターから算出）。
        $fixedCostTotal = '0.00';
        RecurringExpense::activeForFamily($familyId)
            ->get(['id', 'amount', 'day_of_month', 'start_date', 'end_date'])
            ->each(function (RecurringExpense $recurring) use (&$fixedCostTotal, $monthStart) {
                if ($recurring->isDueInMonth($monthStart)) {
                    $fixedCostTotal = bcadd($fixedCostTotal, (string) $recurring->amount, self::SCALE);
                }
            });

        // カテゴリー別消化状況（予算 or 実支出のいずれかが存在するカテゴリーを対象に）。
        $categoryIds = array_values(array_unique(array_merge(
            array_keys($budgetByCategory),
            array_keys($actualByCategory),
        )));

        $categories = array_map(function (string $categoryId) use ($budgetByCategory, $actualByCategory) {
            $budgetAmount = $budgetByCategory[$categoryId] ?? '0.00';
            $actualAmount = $actualByCategory[$categoryId] ?? '0.00';

            return new CategoryUsageData(
                category_id: $categoryId,
                // budgetAmount / actualAmount は decimal:2 キャストまたは bcadd 結果で既に 2 桁固定。
                budget_amount: $budgetAmount,
                actual_amount: $actualAmount,
                remaining: bcsub($budgetAmount, $actualAmount, self::SCALE),
                usage_percent: $this->usagePercent($actualAmount, $budgetAmount),
            );
        }, $categoryIds);

        // 自由に使えるお金 = 収入 − 固定費 − 生活費予算 − 貯金目標（計画ベース）。
        $discretionary = bcsub(
            bcsub(bcsub($totalIncome, $fixedCostTotal, self::SCALE), $categoryBudgetTotal, self::SCALE),
            $savingTarget,
            self::SCALE,
        );

        return new BudgetCalculationResult(
            year_month: $yearMonth,
            // total_income / saving_target は decimal:2 キャストまたは '0.00' で既に 2 桁固定。
            total_income: $totalIncome,
            total_expense: $totalExpense,
            fixed_cost_total: $fixedCostTotal,
            category_budget_total: $categoryBudgetTotal,
            saving_target: $savingTarget,
            discretionary: $discretionary,
            // 貯金可能額 = 収入 − 実支出（実績ベース）。
            possible_saving: bcsub($totalIncome, $totalExpense, self::SCALE),
            // 全体消化率 = 実支出 ÷ 収入（収入未設定なら定義できず null）。
            overall_usage_percent: $this->usagePercent($totalExpense, $totalIncome),
            categories: $categories,
        );
    }

    /**
     * 消化率（%）を小数第 2 位で切り捨てて返す。予算（分母）が 0 以下なら null。
     * 閾値判定（BudgetAlertService）にも使うため half-up 丸めは使わない。
     * 79.996% を 80.00% に切り上げると未達アラートを誤発火させるため、floor 相当の
     * 切り捨てで「実消化率 >= 閾値」の意味を保つ（bc は指定スケールで truncate する）。
     */
    private function usagePercent(string $actual, string $budget): ?string
    {
        if (bccomp($budget, '0', self::SCALE) <= 0) {
            return null;
        }

        return bcadd(bcmul(bcdiv($actual, $budget, 6), '100', 6), '0', self::SCALE);
    }
}

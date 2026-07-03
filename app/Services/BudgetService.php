<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetAlert;
use App\Models\BudgetCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 予算設定（月収入・貯金目標・カテゴリー別予算・アラート）の読み書きを担う。
 * すべて family スコープで動作し、family_id は呼び出し側（コントローラー）が解決済みで渡す。
 */
class BudgetService
{
    /**
     * 当月予算をカテゴリー別予算込みで取得する（未設定月は null）。
     */
    public function findForMonth(string $familyId, string $yearMonth): ?Budget
    {
        return Budget::with('budgetCategories')
            ->where('family_id', $familyId)
            ->where('year_month', $yearMonth)
            ->first();
    }

    /**
     * 家族のアラート設定を取得する（全体 → カテゴリー別の順で安定表示）。
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BudgetAlert>
     */
    public function alertsForFamily(string $familyId)
    {
        return BudgetAlert::where('family_id', $familyId)
            ->orderByRaw('category_id IS NOT NULL') // NULL（全体）を先頭に
            ->orderBy('category_id')
            ->get();
    }

    /**
     * 月収入・貯金目標を upsert する（family_id + year_month 一意）。
     *
     * @param  array<string, mixed>  $attributes  year_month / total_income / saving_target
     */
    public function saveBudget(string $familyId, array $attributes): Budget
    {
        return Budget::updateOrCreate(
            ['family_id' => $familyId, 'year_month' => $attributes['year_month']],
            [
                'total_income' => $attributes['total_income'],
                'saving_target' => $attributes['saving_target'],
            ],
        );
    }

    /**
     * カテゴリー別予算を一括 upsert する。予算行が無ければ先に作成する。
     *
     * @param  array<int, array{category_id: string, amount: string}>  $categoryBudgets
     */
    public function saveCategoryBudgets(string $familyId, string $yearMonth, array $categoryBudgets): void
    {
        DB::transaction(function () use ($familyId, $yearMonth, $categoryBudgets) {
            // カテゴリー別予算を先に設定する導線もあるため、予算行が無ければ既定値（収入・貯金 0）で作成する。
            $budget = Budget::firstOrCreate([
                'family_id' => $familyId,
                'year_month' => $yearMonth,
            ]);

            if ($categoryBudgets === []) {
                return;
            }

            // UNIQUE(budget_id, category_id) を衝突キーに単一 upsert で書き込む（カテゴリー数分のループ発行を避ける）。
            // バルク upsert では HasUuids が発火しないため id は明示生成する（既存行は amount のみ更新され id は不変）。
            $rows = array_map(fn ($row) => [
                'id' => (string) Str::uuid(),
                'budget_id' => $budget->id,
                'category_id' => $row['category_id'],
                'amount' => $row['amount'],
            ], $categoryBudgets);

            BudgetCategory::upsert($rows, ['budget_id', 'category_id'], ['amount']);
        });
    }

    /**
     * 家族のアラート設定を送信内容に同期する。
     * replace-all にすると alert_notifications（重複通知防止履歴）が cascade で消えるため、
     * (family_id, category_id) 一致で updateOrCreate し、送信されなかったものだけを削除する。
     *
     * @param  array<int, array{category_id?: ?string, threshold_percent: int, is_enabled: bool}>  $alerts
     */
    public function saveAlerts(string $familyId, array $alerts): void
    {
        DB::transaction(function () use ($familyId, $alerts) {
            $keptCategoryIds = [];
            $keepOverall = false;

            foreach ($alerts as $row) {
                $categoryId = $row['category_id'] ?? null;

                BudgetAlert::updateOrCreate(
                    ['family_id' => $familyId, 'category_id' => $categoryId],
                    [
                        'threshold_percent' => $row['threshold_percent'],
                        'is_enabled' => $row['is_enabled'],
                    ],
                );

                if ($categoryId === null) {
                    $keepOverall = true;
                } else {
                    $keptCategoryIds[] = $categoryId;
                }
            }

            // 送信されなかったカテゴリー別アラートを削除（NULL 行は whereNotIn に含まれないため別処理）
            BudgetAlert::where('family_id', $familyId)
                ->whereNotNull('category_id')
                ->whereNotIn('category_id', $keptCategoryIds ?: ['-'])
                ->delete();

            // 全体アラートが送信されなければ削除
            if (! $keepOverall) {
                BudgetAlert::where('family_id', $familyId)
                    ->whereNull('category_id')
                    ->delete();
            }
        });
    }
}

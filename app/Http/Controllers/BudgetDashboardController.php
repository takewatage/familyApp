<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\BudgetCalculationResult;
use App\Dtos\Budget\BudgetDashboardPageResult;
use App\Dtos\Model\CategoryData;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\Category;
use App\Services\BudgetCalculationService;
use App\Services\CurrentFamilyService;
use App\Services\RecurringExpenseReminderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetDashboardController extends Controller
{
    use ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly BudgetCalculationService $calculationService,
        private readonly RecurringExpenseReminderService $reminderService,
    ) {}

    /**
     * 家計簿ダッシュボード（F-19）。当月の残高・消化率・固定費リマインダーを集約表示する。
     */
    public function index(Request $request): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $yearMonth = $this->resolveYearMonth($request->query('month'));

        $calculation = $familyId
            ? $this->calculationService->calculate($familyId, $yearMonth)
            : BudgetCalculationResult::zero($yearMonth);

        $reminders = $familyId
            ? $this->reminderService->forMonth($familyId, $yearMonth)
            : [];

        return Inertia::render('Budget/Dashboard', BudgetDashboardPageResult::from([
            'year_month' => $yearMonth,
            'calculation' => $calculation,
            'reminders' => $reminders,
            'categories' => $this->dashboardCategoryOptions($familyId, $calculation),
        ]));
    }

    /**
     * ダッシュボードのカテゴリー参照（名前・色・アイコン）。
     * 有効カテゴリーに加え、当月の予算/支出があるが無効化済みのカテゴリーも補完し、
     * カテゴリー別消化率で「（未分類）」表示になるのを防ぐ。
     *
     * @return CategoryData[]
     */
    private function dashboardCategoryOptions(?string $familyId, BudgetCalculationResult $calculation): array
    {
        $categories = $this->budgetCategoryOptions($familyId);

        if (! $familyId) {
            return $categories;
        }

        $knownIds = array_map(fn (CategoryData $c) => $c->id, $categories);
        $missingIds = array_values(array_diff(
            array_map(fn ($usage) => $usage->category_id, $calculation->categories),
            $knownIds,
        ));

        if ($missingIds === []) {
            return $categories;
        }

        // 無効化済みでも家族/システム既定スコープ内のものだけを補完（越境しない）。
        $extra = Category::whereIn('id', $missingIds)
            ->where(function ($query) use ($familyId) {
                $query->where('family_id', $familyId)->orWhere('is_system', true);
            })
            ->get()
            ->map(fn (Category $c) => CategoryData::from($c))
            ->all();

        return [...$categories, ...$extra];
    }
}

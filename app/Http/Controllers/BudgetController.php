<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\BudgetSettingsPageResult;
use App\Dtos\Budget\StoreBudgetAlertsRequest;
use App\Dtos\Budget\StoreBudgetCategoriesRequest;
use App\Dtos\Budget\StoreBudgetRequest;
use App\Dtos\Model\BudgetAlertData;
use App\Dtos\Model\BudgetCategoryData;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\BudgetAlert;
use App\Models\BudgetCategory;
use App\Services\BudgetService;
use App\Services\CurrentFamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly BudgetService $budgetService,
    ) {}

    public function show(Request $request): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $yearMonth = $this->resolveYearMonth($request->query('month'));

        $budget = $familyId ? $this->budgetService->findForMonth($familyId, $yearMonth) : null;
        $alerts = $familyId ? $this->budgetService->alertsForFamily($familyId) : collect();

        return Inertia::render('Budget/BudgetSettings', BudgetSettingsPageResult::from([
            'year_month' => $yearMonth,
            // 未設定月も decimal:2 と同じ '0.00' 形式で返す（設定月の '300000.00' と shape を揃える）
            'total_income' => $budget ? (string) $budget->total_income : '0.00',
            'saving_target' => $budget ? (string) $budget->saving_target : '0.00',
            'category_budgets' => $budget
                ? $budget->budgetCategories->map(fn (BudgetCategory $bc) => BudgetCategoryData::fromModel($bc))->all()
                : [],
            'alerts' => $alerts->map(fn (BudgetAlert $a) => BudgetAlertData::fromModel($a))->all(),
            'categories' => $this->budgetCategoryOptions($familyId),
        ]));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $this->budgetService->saveBudget($familyId, $request->toArray());

        return back();
    }

    public function storeCategories(StoreBudgetCategoriesRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $this->budgetService->saveCategoryBudgets($familyId, $request->year_month, $request->toArray()['category_budgets']);

        return back();
    }

    public function storeAlerts(StoreBudgetAlertsRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $this->budgetService->saveAlerts($familyId, $request->toArray()['alerts']);

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\ExpensePageResult;
use App\Dtos\Budget\StoreExpenseRequest;
use App\Dtos\Budget\UpdateExpenseRequest;
use App\Dtos\Model\ExpenseData;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\Expense;
use App\Services\CurrentFamilyService;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    use AuthorizesFamilyOwnership, ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly ExpenseService $expenseService,
    ) {}

    public function index(Request $request): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $yearMonth = $this->resolveYearMonth($request->query('month'));

        $expenses = $familyId
            ? $this->expenseService->listForMonth($familyId, $yearMonth)
            : collect();

        $expenseData = $expenses->map(fn (Expense $e) => ExpenseData::fromModel($e))->all();
        $totalAmount = (string) $expenses->sum(fn (Expense $e) => (float) $e->amount);

        return Inertia::render('Budget/ExpenseIndex', ExpensePageResult::from([
            'expenses' => $expenseData,
            'categories' => $this->budgetCategoryOptions($familyId),
            'payment_methods' => $this->budgetPaymentMethodOptions($familyId),
            'shops' => $this->budgetShopOptions($familyId),
            'quick_entries' => $this->budgetQuickEntryOptions($familyId),
            'member_options' => $this->budgetMemberOptions($familyId),
            'year_month' => $yearMonth,
            'total_amount' => $totalAmount,
        ]));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $this->expenseService->create($familyId, $request->toArray());

        return back();
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorizeFamilyOwnership($expense);

        $this->expenseService->update($expense, $request->toArray());

        return back();
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorizeFamilyOwnership($expense);

        $this->expenseService->delete($expense);

        return back();
    }
}

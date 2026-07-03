<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\RecurringExpensesPageResult;
use App\Dtos\Budget\StoreRecurringExpenseRequest;
use App\Dtos\Model\RecurringExpenseData;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\RecurringExpense;
use App\Services\CurrentFamilyService;
use App\Services\RecurringExpenseService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecurringExpenseController extends Controller
{
    use AuthorizesFamilyOwnership, ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly RecurringExpenseService $recurringExpenseService,
    ) {}

    public function index(): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $recurringExpenses = $familyId
            ? RecurringExpense::with(['category', 'paymentMethod', 'shop', 'member'])
                ->where('family_id', $familyId)
                ->where('is_active', true)
                ->orderBy('day_of_month')
                ->orderBy('name')
                ->get()
                ->map(fn (RecurringExpense $r) => RecurringExpenseData::fromModel($r))
                ->all()
            : [];

        return Inertia::render('Budget/RecurringExpenses', RecurringExpensesPageResult::from([
            'recurring_expenses' => $recurringExpenses,
            'categories' => $this->budgetCategoryOptions($familyId),
            'payment_methods' => $this->budgetPaymentMethodOptions($familyId),
            'shops' => $this->budgetShopOptions($familyId),
            'member_options' => $this->budgetMemberOptions($familyId),
        ]));
    }

    public function store(StoreRecurringExpenseRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $this->recurringExpenseService->create($familyId, $request->toArray());

        return back();
    }

    public function update(StoreRecurringExpenseRequest $request, RecurringExpense $recurringExpense): RedirectResponse
    {
        $this->authorizeFamilyOwnership($recurringExpense);

        $this->recurringExpenseService->update($recurringExpense, $request->toArray());

        return back();
    }

    public function destroy(RecurringExpense $recurringExpense): RedirectResponse
    {
        // 生成済み支出の参照整合性を保つため物理削除せず、無効化（is_active=false）する。
        // 以降のバッチ生成対象から外れ、一覧（is_active=true のみ）からも消える。
        $this->authorizeFamilyOwnership($recurringExpense);

        $recurringExpense->update(['is_active' => false]);

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\ExpensePageResult;
use App\Dtos\Budget\MemberOptionData;
use App\Dtos\Budget\StoreExpenseRequest;
use App\Dtos\Budget\UpdateExpenseRequest;
use App\Dtos\Model\ExpenseData;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\Expense;
use App\Models\User;
use App\Models\VirtualUser;
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
            'member_options' => $this->memberOptions($familyId),
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

    private function resolveYearMonth(?string $month): string
    {
        // 月は 01-12 のみ許可する。範囲外（00・13-99）を通すと Carbon が別月/別年へ桁上がりし、
        // ヘッダー表示（$yearMonth）と実際の集計月がずれるため正規表現で弾く。
        if ($month && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $month;
        }

        return now()->format('Y-m');
    }

    /**
     * 担当者の選択肢（実ユーザー + 仮想ユーザー）。
     *
     * @return MemberOptionData[]
     */
    private function memberOptions(?string $familyId): array
    {
        if (! $familyId) {
            return [];
        }

        $family = $this->currentFamilyService->getCurrentFamily();

        if (! $family) {
            return [];
        }

        $options = [];

        foreach ($family->members as $user) {
            $options[] = new MemberOptionData(
                key: User::class.'|'.$user->id,
                member_type: User::class,
                member_id: $user->id,
                name: $user->name,
            );
        }

        foreach ($family->virtualUsers as $vu) {
            $options[] = new MemberOptionData(
                key: VirtualUser::class.'|'.$vu->id,
                member_type: VirtualUser::class,
                member_id: $vu->id,
                name: $vu->name,
            );
        }

        return $options;
    }
}

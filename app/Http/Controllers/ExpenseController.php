<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\ExpensePageResult;
use App\Dtos\Budget\MemberOptionData;
use App\Dtos\Budget\StoreExpenseRequest;
use App\Dtos\Budget\UpdateExpenseRequest;
use App\Dtos\Model\CategoryData;
use App\Dtos\Model\ExpenseData;
use App\Dtos\Model\PaymentMethodData;
use App\Dtos\Model\ShopData;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Shop;
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
    use AuthorizesFamilyOwnership;

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
            'categories' => $this->categoryOptions($familyId),
            'payment_methods' => $this->paymentMethodOptions($familyId),
            'shops' => $this->shopOptions($familyId),
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
        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        return now()->format('Y-m');
    }

    /**
     * 家族のカテゴリー + システムデフォルトを並び順で返す。
     *
     * @return CategoryData[]
     */
    private function categoryOptions(?string $familyId): array
    {
        return Category::activeOptions($familyId)
            ->get()
            ->map(fn (Category $c) => CategoryData::from($c))
            ->all();
    }

    /**
     * @return PaymentMethodData[]
     */
    private function paymentMethodOptions(?string $familyId): array
    {
        return PaymentMethod::activeOptions($familyId)
            ->get()
            ->map(fn (PaymentMethod $p) => PaymentMethodData::from($p))
            ->all();
    }

    /**
     * @return ShopData[]
     */
    private function shopOptions(?string $familyId): array
    {
        if (! $familyId) {
            return [];
        }

        return Shop::forFamilyOrdered($familyId)
            ->get()
            ->map(fn (Shop $s) => ShopData::from($s))
            ->all();
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

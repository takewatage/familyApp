<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\PaymentMethodsPageResult;
use App\Dtos\Budget\StorePaymentMethodRequest;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\PaymentMethod;
use App\Services\CurrentFamilyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    use AuthorizesFamilyOwnership, ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    public function index(): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        return Inertia::render('Budget/PaymentMethods', PaymentMethodsPageResult::from([
            'payment_methods' => $this->budgetPaymentMethodOptions($familyId),
        ]));
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        // 並び順はシステム既定（sort_order 0-4）も含めた最大値の次にする。
        // 家族行のみで max を取るとシステム既定の値と衝突し、一覧でカスタム方法が既定の間に割り込むため。
        $maxSort = PaymentMethod::visibleTo($familyId)->max('sort_order');

        PaymentMethod::create([
            'family_id' => $familyId,
            'name' => $request->name,
            'icon' => $request->icon,
            'sort_order' => ($maxSort ?? 0) + 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        return back();
    }

    public function update(StorePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        // システム既定（family_id=null）は family 不一致で 404 になり、編集から保護される。
        $this->authorizeFamilyOwnership($paymentMethod);

        $paymentMethod->update([
            'name' => $request->name,
            'icon' => $request->icon,
        ]);

        return back();
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        // システム既定は 404 で保護される。家族固有のものは論理削除（支出の参照整合性を保つため物理削除しない）。
        $this->authorizeFamilyOwnership($paymentMethod);

        $paymentMethod->update(['is_active' => false]);

        return back();
    }
}

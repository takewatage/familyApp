<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\QuickEntriesPageResult;
use App\Dtos\Budget\StoreQuickEntryRequest;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\QuickEntry;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuickEntryController extends Controller
{
    use AuthorizesFamilyOwnership, ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    public function index(): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        return Inertia::render('Budget/QuickEntries', QuickEntriesPageResult::from([
            'quick_entries' => $this->budgetQuickEntryOptions($familyId),
            'categories' => $this->budgetCategoryOptions($familyId),
            'payment_methods' => $this->budgetPaymentMethodOptions($familyId),
            'shops' => $this->budgetShopOptions($familyId),
        ]));
    }

    public function store(StoreQuickEntryRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $maxSort = QuickEntry::where('family_id', $familyId)->max('sort_order');

        QuickEntry::create([
            'family_id' => $familyId,
            'name' => $request->name,
            'category_id' => $request->category_id,
            'payment_method_id' => $request->payment_method_id,
            'shop_id' => $request->shop_id,
            'default_amount' => $request->default_amount,
            'sort_order' => ($maxSort ?? 0) + 1,
            'usage_count' => 0,
        ]);

        return back();
    }

    public function update(StoreQuickEntryRequest $request, QuickEntry $quickEntry): RedirectResponse
    {
        $this->authorizeFamilyOwnership($quickEntry);

        $quickEntry->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'payment_method_id' => $request->payment_method_id,
            'shop_id' => $request->shop_id,
            'default_amount' => $request->default_amount,
        ]);

        return back();
    }

    public function destroy(QuickEntry $quickEntry): RedirectResponse
    {
        // クイック入力は支出から参照されないため物理削除する。
        $this->authorizeFamilyOwnership($quickEntry);

        $quickEntry->delete();

        return back();
    }

    /**
     * 支出フォームへのプリセット時に利用回数を加算する（並び順を利用頻度に追従させる）。
     * 画面遷移不要の静かな更新のため axios + JSON を使う（CRUD は Inertia）。
     */
    public function use(QuickEntry $quickEntry): JsonResponse
    {
        $this->authorizeFamilyOwnership($quickEntry);

        $quickEntry->increment('usage_count');

        return response()->json(['success' => true]);
    }
}

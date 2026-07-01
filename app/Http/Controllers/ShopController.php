<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\ShopsPageResult;
use App\Dtos\Budget\StoreShopRequest;
use App\Dtos\Budget\UpdateShopRequest;
use App\Dtos\Model\ShopData;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Http\Controllers\Concerns\ProvidesBudgetOptions;
use App\Models\Shop;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    use AuthorizesFamilyOwnership, ProvidesBudgetOptions;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    public function index(): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        return Inertia::render('Budget/Shops', ShopsPageResult::from([
            'shops' => $this->budgetShopOptions($familyId),
            'categories' => $this->budgetCategoryOptions($familyId),
        ]));
    }

    public function store(StoreShopRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        Shop::create([
            'family_id' => $familyId,
            'name' => $request->name,
            'default_category_id' => $request->default_category_id,
            'usage_count' => 0,
        ]);

        return back();
    }

    public function update(UpdateShopRequest $request, Shop $shop): RedirectResponse
    {
        $this->authorizeFamilyOwnership($shop);

        $shop->update([
            'name' => $request->name,
            'default_category_id' => $request->default_category_id,
        ]);

        return back();
    }

    public function destroy(Shop $shop): RedirectResponse
    {
        $this->authorizeFamilyOwnership($shop);

        // expenses.shop_id は nullOnDelete。物理削除で参照は自動的に NULL 化される。
        $shop->delete();

        return back();
    }

    /**
     * 店名オートコンプリート（Axios, JSON）。家族スコープ・利用回数降順。
     *
     * @return JsonResponse<ShopData[]>
     */
    public function search(Request $request): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        if (! $familyId) {
            return response()->json([]);
        }

        $keyword = trim((string) $request->query('q', ''));

        $shops = Shop::forFamilyOrdered($familyId)
            ->when($keyword !== '', function ($q) use ($keyword) {
                // LIKE のワイルドカード (% _ \) をエスケープし、メタ文字での意図しない広域一致を防ぐ
                $escaped = addcslashes($keyword, '%_\\');
                $q->where('name', 'like', '%'.$escaped.'%');
            })
            ->limit(20)
            ->get()
            ->map(fn (Shop $s) => ShopData::from($s))
            ->all();

        return response()->json($shops);
    }
}

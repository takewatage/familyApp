<?php

namespace App\Http\Controllers;

use App\Dtos\Budget\CategoriesPageResult;
use App\Dtos\Budget\ReorderCategoriesRequest;
use App\Dtos\Budget\StoreCategoryRequest;
use App\Dtos\Budget\UpdateCategoryRequest;
use App\Dtos\Model\CategoryData;
use App\Http\Controllers\Concerns\AuthorizesFamilyOwnership;
use App\Models\Category;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AuthorizesFamilyOwnership;

    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    public function index(): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $categories = Category::activeOptions($familyId)
            ->get()
            ->map(fn (Category $c) => CategoryData::from($c))
            ->all();

        return Inertia::render('Budget/Categories', CategoriesPageResult::from([
            'categories' => $categories,
        ]));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        $maxSort = Category::where('family_id', $familyId)->max('sort_order');

        Category::create([
            'family_id' => $familyId,
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'icon' => $request->icon,
            'color' => $request->color,
            'sort_order' => ($maxSort ?? 0) + 1,
            'is_system' => false,
            'is_active' => true,
        ]);

        return back();
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorizeFamilyOwnership($category);
        $this->assertValidParent($category, $request->parent_id);

        $category->update([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'icon' => $request->icon,
            'color' => $request->color,
        ]);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorizeFamilyOwnership($category);

        // 論理削除。支出の参照整合性を保つため物理削除しない。子カテゴリーも併せて無効化する。
        $category->children()->update(['is_active' => false]);
        $category->update(['is_active' => false]);

        return back();
    }

    public function reorder(ReorderCategoriesRequest $request): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        abort_if(! $familyId, 403, '家族が選択されていません');

        // 途中失敗で sort_order が一部だけ更新される不整合を防ぐため、まとめて 1 トランザクションで反映する。
        DB::transaction(function () use ($request, $familyId) {
            foreach ($request->categories as $item) {
                Category::where('id', $item->id)
                    ->where('family_id', $familyId)
                    ->update(['sort_order' => $item->sort]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * 親カテゴリー指定の妥当性を検証する（自己参照・循環・子を持つカテゴリーの子化を防ぐ）。
     * 親の可視性・最上位制約は UpdateCategoryRequest（StoreCategoryRequest 継承）の rules で担保済み。
     */
    private function assertValidParent(Category $category, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $category->id) {
            throw ValidationException::withMessages(['parent_id' => '自分自身を親カテゴリーにはできません。']);
        }

        // 自身が子を持つ場合、親を付けると 3 階層になるため禁止（親子 2 階層制限）。
        // 論理削除済みの子も対象に含める（将来の復元で 3 階層が復活する不整合を防ぐ）。
        if ($category->children()->exists()) {
            throw ValidationException::withMessages(['parent_id' => '子カテゴリーを持つため、別カテゴリーの下に移動できません。']);
        }
    }
}

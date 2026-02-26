<?php

namespace App\Http\Controllers;

use App\Dtos\Dok\TaskCategoryData;
use App\Models\TaskCategory;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskCategoryController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $maxSort = TaskCategory::where('family_id', $familyId)->max('sort');

        $category = TaskCategory::create([
            'family_id' => $familyId,
            'name' => $validated['name'],
            'sort' => ($maxSort ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'category' => TaskCategoryData::from($category->toArray()),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $taskCategory = TaskCategory::where('id', $id)
            ->where('family_id', $familyId)
            ->firstOrFail();

        $taskCategory->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'success' => true,
            'category' => TaskCategoryData::from($taskCategory->toArray()),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $taskCategory = TaskCategory::where('id', $id)
            ->where('family_id', $familyId)
            ->firstOrFail();

        $taskCategory->tasks()->delete();
        $taskCategory->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => ['required', 'string', Rule::exists('task_categories', 'id')->where('family_id', $familyId)],
            'orders.*.sort' => 'required|integer',
        ]);

        foreach ($validated['orders'] as $order) {
            TaskCategory::where('id', $order['id'])
                ->where('family_id', $familyId)
                ->update(['sort' => $order['sort']]);
        }

        return response()->json(['success' => true]);
    }
}
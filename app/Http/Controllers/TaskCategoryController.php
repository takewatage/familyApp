<?php

namespace App\Http\Controllers;

use App\Dtos\Dok\TaskCategoryData;
use App\Models\TaskCategory;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskCategoryController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50',
        ]);

        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $maxSort = TaskCategory::where('family_id', $familyId)->max('sort');

        $category = TaskCategory::create([
            'family_id' => $familyId,
            'name' => $validated['name'],
            'color' => $validated['color'],
            'sort' => ($maxSort ?? 0) + 1,
        ]);

        return response()->json([
            'success' => true,
            'category' => TaskCategoryData::from($category->toArray()),
        ]);
    }

    public function update(Request $request, TaskCategory $taskCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50',
        ]);

        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        if ($taskCategory->family_id !== $familyId) {
            abort(403);
        }

        $taskCategory->update([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return response()->json([
            'success' => true,
            'category' => TaskCategoryData::from($taskCategory->toArray()),
        ]);
    }

    public function destroy(TaskCategory $taskCategory): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        if ($taskCategory->family_id !== $familyId) {
            abort(403);
        }

        $taskCategory->tasks()->delete();
        $taskCategory->delete();

        return response()->json(['success' => true]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|string|exists:task_categories,id',
            'orders.*.sort' => 'required|integer',
        ]);

        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        foreach ($validated['orders'] as $order) {
            TaskCategory::where('id', $order['id'])
                ->where('family_id', $familyId)
                ->update(['sort' => $order['sort']]);
        }

        return response()->json(['success' => true]);
    }
}
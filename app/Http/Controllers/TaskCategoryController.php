<?php

namespace App\Http\Controllers;

use App\Dtos\Task\SortTaskCategoryRequest;
use App\Dtos\Task\StoreTaskCategoryRequest;
use App\Dtos\Task\TaskCategoryData;
use App\Dtos\Task\TaskCategoryResult;
use App\Dtos\Task\UpdateTaskCategoryRequest;
use App\Models\TaskCategory;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;

class TaskCategoryController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function store(StoreTaskCategoryRequest $request): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $maxSort = TaskCategory::where('family_id', $familyId)->max('sort');

        $category = TaskCategory::create([
            'family_id' => $familyId,
            'name' => $request->name,
            'sort' => ($maxSort ?? 0) + 1,
        ]);

        return response()->json(new TaskCategoryResult(
            success: true,
            category: TaskCategoryData::from($category->toArray()),
        ));
    }

    public function update(UpdateTaskCategoryRequest $request, string $id): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $taskCategory = TaskCategory::where('id', $id)
            ->where('family_id', $familyId)
            ->firstOrFail();

        $taskCategory->update([
            'name' => $request->name,
        ]);

        return response()->json(new TaskCategoryResult(
            success: true,
            category: TaskCategoryData::from($taskCategory->toArray()),
        ));
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

    public function reorder(SortTaskCategoryRequest $request): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        foreach ($request->categories as $item) {
            TaskCategory::where('id', $item->id)
                ->where('family_id', $familyId)
                ->update(['sort' => $item->sort]);
        }

        return response()->json(['success' => true]);
    }
}

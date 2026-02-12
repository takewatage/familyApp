<?php

namespace App\Http\Controllers;


use App\Dtos\Dok\TaskPageData;
use App\Dtos\Dok\TaskCategoryData;
use App\Dtos\Dok\TaskData;
use App\Events\TaskUpdated;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;


class TaskController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService
    )
    {
    }

    public function index(Request $request): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $categories = [];
        $tasks = [];

        if ($familyId) {
            $categories = TaskCategory::where('family_id', $familyId)
                ->orderBy('sort')
                ->get()
                ->toArray();
            $tasks = Task::where('family_id', $familyId)
                ->orderBy('sort')
                ->get()
                ->toArray();
        }

        $dto = TaskPageData::from([
            'categories' => $categories,
            'tasks' => $tasks,
            'familyId' => $familyId,
        ]);

        return Inertia::render('Task/index', $dto->toArray());
    }

    /**
     * タスクの完了状態をトグル
     */
    public function toggle(Task $task): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        // 権限チェック
        if ($task->family_id !== $familyId) {
            abort(403);
        }

        $task->update([
            'is_completed' => !$task->is_completed,
        ]);

        // リアルタイム通知
        broadcast(new TaskUpdated($task, 'updated'))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:255',
            'category_id' => 'required|exists:task_categories,id',
        ]);

        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $maxSort = Task::where('family_id', $familyId)
            ->where('category_id', $validated['category_id'])
            ->max('sort');

        $task = Task::create([
            'family_id' => $familyId,
            'category_id' => $validated['category_id'],
            'content' => $validated['content'],
            'is_completed' => false,
            'sort' => ($maxSort ?? 0) + 1,
        ]);

        broadcast(new TaskUpdated($task, 'created'))->toOthers();

        return response()->json([
            'success' => true,
            'task' => TaskData::from($task->toArray()),
        ]);
    }

    /**
     * タスク削除
     */
    public function destroy(Task $task): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        if ($task->family_id !== $familyId) {
            abort(403);
        }

        $taskData = $task->toArray();
        $task->delete();

        broadcast(new TaskUpdated(
            new Task($taskData),
            'deleted'
        ))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }
}

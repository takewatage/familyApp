<?php

namespace App\Http\Controllers;

use App\Dtos\Dok\TaskPageData;
use App\Dtos\Task\SaveTaskRequestData;
use App\Dtos\Task\TaskData;
use App\Events\TaskUpdated;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Services\CurrentFamilyService;
use Hamcrest\Core\AllOf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function index(): Response
    {
        $family_id = $this->currentFamilyService->getCurrentFamilyId();
        $categories = [];
        $tasks = [];

        if ($family_id) {
            $categories = TaskCategory::where('family_id', $family_id)->orderBy('sort')->get()->toArray();
            $tasks = Task::where('family_id', $family_id)
                ->where(function ($query) {
                    $query->where('is_completed', false)->orWhere(function ($q) {
                        $q->where('is_completed', true)->where('completed_at', '>=', now()->subDays(7));
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        }

        $dto = TaskPageData::from([
            'categories' => $categories,
            'tasks' => $tasks,
            'family_id' => $family_id,
        ]);

        return Inertia::render('Task/index', $dto->toArray());
    }

    /**
     * タスクの完了状態をトグル
     */
    public function toggle(string $task_id): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $task = Task::find($task_id);

        if (!$task) {
            ValidationException::withMessages(['task' => 'タスクが見つかりません。']);
        }

        // 権限チェック
        if ($task->family_id !== $familyId) {
            abort(403);
        }

        $isCompleted = !$task->is_completed;

        $task->update([
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        // タスクを再読み込みして最新の状態を取得
        $task->refresh();

        // リアルタイム通知
        broadcast(new TaskUpdated($task, 'updated'))->toOthers();

        return response()->json([
            'success' => true,
            'task' => TaskData::from($task->toArray()),
        ]);
    }

    /**
     * @param SaveTaskRequestData $request
     * @return JsonResponse
     */
    public function store(SaveTaskRequestData $request): JsonResponse
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        $maxSort = Task::where('family_id', $familyId)
            ->where('category_id', $request->category_id)
            ->max('sort');

        $task = Task::create([
            'family_id' => $familyId,
            'category_id' => $request->category_id,
            'content' => $request->content,
            'color' => $request->color,
            'memo' => $request->memo,
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
     * タスク更新
     * @throws ValidationException
     */
    public function update(Request $request, SaveTaskRequestData $data): JsonResponse
    {
        if (!$data->id) {
            throw ValidationException::withMessages([
                'task' => 'タスクが存在しません。'
            ]);
        }

        $familyId = $this->currentFamilyService->getCurrentFamilyId();

        // 権限チェック
        if ($data->family_id !== $familyId) {
            abort(403);
        }

        $task = Task::find($data->id);

        $task->update([
            'content' => $data->content,
            'color' => $data->color,
            'memo' => $data->memo ?? null,
        ]);

        // リアルタイム通知
        broadcast(new TaskUpdated($task, 'updated'))->toOthers();

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

        broadcast(new TaskUpdated(new Task($taskData), 'deleted'))->toOthers();

        return response()->json([
            'success' => true,
        ]);
    }
}

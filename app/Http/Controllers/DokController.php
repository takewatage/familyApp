<?php

namespace App\Http\Controllers;


use App\Dtos\Dok\DokResultData;
use App\Dtos\Dok\TaskCategoryData;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Services\CurrentFamilyService;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;


class DokController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService
    )
    {
    }


    public function index(): Response
    {
        $familyId = $this->currentFamilyService->getCurrentFamilyId();
        $categories = [];
        $tasks = [];

        if ($familyId) {
            $categories = TaskCategory::where('family_id', $familyId)
                ->get()
                ->toArray();
            $tasks = Task::where('family_id', $familyId)
                ->get()
                ->toArray();
        }

        $dto = DokResultData::from([
            'categories' => $categories,
            'tasks' => $tasks,
        ]);

        return Inertia::render('Dok/index', $dto->toArray());
    }
}

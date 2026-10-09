<?php

namespace App\Http\Controllers;

use App\Dtos\Calendar\UpdateCalendarLabelsRequest;
use App\Services\CalendarLabelService;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;

/**
 * カレンダーのラベル API（家族で共有。家族全員が編集できる）
 */
class CalendarLabelController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly CalendarLabelService $calendarLabelService,
    ) {}

    /**
     * ラベル一覧の一括更新（名前・カラー・並び順）
     */
    public function update(UpdateCalendarLabelsRequest $data): JsonResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        abort_if(!$family, 404);

        return response()->json([
            'labels' => $this->calendarLabelService->update($family, $data->labels),
        ]);
    }
}

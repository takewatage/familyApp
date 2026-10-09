<?php

namespace App\Http\Controllers;

use App\Dtos\Calendar\UpdateCalendarSettingsRequest;
use App\Services\CalendarSettingsService;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * カレンダー設定 API（家族で共有。家族全員が変更できる）
 */
class CalendarSettingsController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly CalendarSettingsService $calendarSettingsService,
    ) {}

    public function update(UpdateCalendarSettingsRequest $data, Request $request): JsonResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        abort_if(!$family, 404);

        // 送られた項目だけを更新する（未送信の項目は変えない）
        $values = array_intersect_key($data->toArray(), $request->all());

        return response()->json([
            'settings' => $this->calendarSettingsService->update($family, $values),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Dtos\Calendar\CalendarEventRequest;
use App\Dtos\Calendar\CalendarEventsRequest;
use App\Dtos\Calendar\DeleteCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\Family;
use App\Services\CalendarEventService;
use App\Services\CurrentFamilyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * カレンダーの予定 API（Axios・JSON）。家族全員が作成・編集・削除できる
 */
class CalendarEventController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly CalendarEventService $calendarEventService,
    ) {}

    /**
     * 期間内の予定（繰り返しを展開済み・誕生日を含む）
     */
    public function index(CalendarEventsRequest $data): JsonResponse
    {
        $family = $this->family();

        return response()->json([
            'events' => $this->calendarEventService->occurrences($family, Carbon::parse($data->from), Carbon::parse($data->to)),
        ]);
    }

    public function store(CalendarEventRequest $data, Request $request): JsonResponse
    {
        $event = $this->calendarEventService->create($this->family(), $request->user(), $data);

        return response()->json(['id' => $event->id], 201);
    }

    public function update(CalendarEventRequest $data, string $calendarEvent): JsonResponse
    {
        $family = $this->family();
        $this->calendarEventService->update($family, $this->findEvent($family, $calendarEvent), $data);

        return response()->json(['success' => true]);
    }

    public function destroy(DeleteCalendarEventRequest $data, string $calendarEvent): JsonResponse
    {
        $family = $this->family();
        $this->calendarEventService->delete($this->findEvent($family, $calendarEvent), $data->scope, $data->occurrence_date);

        return response()->json(['success' => true]);
    }

    private function family(): Family
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        abort_if(!$family, 404);

        return $family;
    }

    /**
     * 現在の家族の予定だけを対象にする（他の家族の予定は 404）
     */
    private function findEvent(Family $family, string $id): CalendarEvent
    {
        return CalendarEvent::where('family_id', $family->id)->findOrFail($id);
    }
}

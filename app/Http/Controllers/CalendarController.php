<?php

namespace App\Http\Controllers;

use App\Dtos\Calendar\CalendarPageResult;
use App\Dtos\Calendar\CalendarParticipantResult;
use App\Dtos\Family\FamilyMemberData;
use App\Dtos\Model\VirtualUserData;
use App\Services\CalendarLabelService;
use App\Services\CalendarSettingsService;
use App\Services\CurrentFamilyService;
use App\Services\FamilyMemberService;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly FamilyMemberService $familyMemberService,
        private readonly CalendarLabelService $calendarLabelService,
        private readonly CalendarSettingsService $calendarSettingsService,
    ) {}

    /**
     * カレンダー画面。
     *
     * 参加者の選択肢（現在の家族のメンバー・仮想ユーザー）とラベルを渡す。
     * 予定は表示中の月に合わせて画面から GET /calendar/events で取得する。
     */
    public function index(): Response
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return Inertia::render('Calendar/Index', CalendarPageResult::from(['participants' => []]));
        }

        $members = array_map(fn (FamilyMemberData $m) => new CalendarParticipantResult(
            id: $m->id,
            name: $m->name,
            avatar_url: $m->avatar?->url,
            is_virtual: false,
        ), $this->familyMemberService->members($family));

        $virtualUsers = array_map(fn (VirtualUserData $vu) => new CalendarParticipantResult(
            id: $vu->id,
            name: $vu->name,
            avatar_url: $vu->avatar?->url,
            is_virtual: true,
        ), $this->familyMemberService->virtualUsers($family));

        return Inertia::render('Calendar/Index', CalendarPageResult::from([
            'participants' => [...$members, ...$virtualUsers],
            'labels' => $this->calendarLabelService->list($family),
            'settings' => $this->calendarSettingsService->get($family),
        ]));
    }
}

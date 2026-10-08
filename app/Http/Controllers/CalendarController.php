<?php

namespace App\Http\Controllers;

use App\Dtos\Calendar\CalendarPageResult;
use App\Dtos\Calendar\CalendarParticipantResult;
use App\Dtos\Family\FamilyMemberData;
use App\Dtos\Model\VirtualUserData;
use App\Services\CurrentFamilyService;
use App\Services\FamilyMemberService;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly FamilyMemberService $familyMemberService,
    ) {}

    /**
     * カレンダー画面（モックアップ）。
     *
     * 予定は未確定のため、表示用のダミーイベントはページ側（Pages/Calendar/Index.vue）で保持する。
     * 予定の参加者として選べるよう、現在の家族のメンバー（仮想ユーザー含む）だけを渡す。
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
        ]));
    }
}

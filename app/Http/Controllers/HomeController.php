<?php

namespace App\Http\Controllers;

use App\Dtos\Home\HomeResult;
use App\Services\CurrentFamilyService;
use App\Services\FamilyMemberService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly FamilyMemberService $familyMemberService,
    ) {}

    public function index(): Response
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return Inertia::render('Home', HomeResult::from([
                'members' => [],
                'virtual_users' => [],
            ]));
        }

        return Inertia::render('Home', HomeResult::from([
            'members' => $this->familyMemberService->members($family),
            'virtual_users' => $this->familyMemberService->virtualUsers($family),
        ]));
    }
}

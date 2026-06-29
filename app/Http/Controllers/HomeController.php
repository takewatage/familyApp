<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilyMemberData;
use App\Dtos\Home\HomeResult;
use App\Dtos\Model\FileData;
use App\Dtos\Model\VirtualUserData;
use App\Models\User;
use App\Services\CurrentFamilyService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
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

        $family->load(['members.files', 'virtualUsers.files']);

        $members = $family->members->map(function (User $user) {
            $avatarFile = $user->files->firstWhere('collection', 'avatar');

            return new FamilyMemberData(
                id: $user->id,
                name: $user->name,
                role: $user->pivot->role,
                avatar: $avatarFile ? FileData::from($avatarFile->toArray()) : null,
            );
        })->values()->all();

        $virtualUsers = $family->virtualUsers->map(function ($vu) {
            $avatarFile = $vu->files->firstWhere('collection', 'avatar');

            return new VirtualUserData(
                id: $vu->id,
                family_id: $vu->family_id,
                name: $vu->name,
                created_at: $vu->created_at?->toIso8601String(),
                updated_at: $vu->updated_at?->toIso8601String(),
                avatar: $avatarFile ? FileData::from($avatarFile->toArray()) : null,
            );
        })->values()->all();

        return Inertia::render('Home', HomeResult::from([
            'members' => $members,
            'virtual_users' => $virtualUsers,
        ]));
    }
}

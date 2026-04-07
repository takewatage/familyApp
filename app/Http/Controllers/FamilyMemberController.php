<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilyMemberData;
use App\Dtos\Family\FamilyMembersResult;
use App\Dtos\Model\FileData;
use App\Dtos\Model\VirtualUserData;
use App\Models\User;
use App\Services\CurrentFamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
class FamilyMemberController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function index(): Response|RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
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
        });

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
        });

        $inviteUrls = [];
        foreach (['parent', 'child', 'guest'] as $role) {
            $inviteUrls[$role] = URL::signedRoute('invite.show', [
                'code' => $family->code,
                'role' => $role,
            ]);
        }

        return Inertia::render('MyPage/FamilyMembers', FamilyMembersResult::from([
            'family' => $family->toArray(),
            'members' => $members->values(),
            'virtual_users' => $virtualUsers->values(),
            'is_owner' => $family->owner_id === auth()->id(),
            'invite_urls' => $inviteUrls,
        ]));
    }

    public function destroy(User $user): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        $this->authorize('removeMember', $family);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['member' => '自分自身を除名することはできません']);
        }

        $family->members()->detach($user->id);

        return back()->with('message', 'メンバーを除名しました');
    }
}

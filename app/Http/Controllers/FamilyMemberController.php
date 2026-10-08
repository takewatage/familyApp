<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilyMembersResult;
use App\Models\User;
use App\Services\CurrentFamilyService;
use App\Services\FamilyMemberService;
use App\Services\InviteUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly InviteUrlService $inviteUrlService,
        private readonly FamilyMemberService $familyMemberService,
    ) {}

    public function index(): Response|RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        $inviteUrls = $this->inviteUrlService->generateInviteUrls($family);

        return Inertia::render('MyPage/FamilyMembers', FamilyMembersResult::from([
            'family' => $family->toArray(),
            'members' => $this->familyMemberService->members($family),
            'virtual_users' => $this->familyMemberService->virtualUsers($family),
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

        Gate::authorize('removeMember', $family);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['member' => '自分自身を除名することはできません']);
        }

        $family->members()->detach($user->id);

        return back()->with('message', 'メンバーを除名しました');
    }
}

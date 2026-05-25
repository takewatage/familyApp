<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilyForSwitchData;
use App\Dtos\Family\FamilySwitchResult;
use App\Models\Family;
use App\Services\CurrentFamilyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
class FamilySwitchController extends Controller
{
    public function __construct(private readonly CurrentFamilyService $currentFamilyService)
    {
    }

    public function index(): Response
    {
        $user = auth()->user();
        $families = $user->families()->with('members:id,name')->get();

        $familiesData = $families->map(fn($family) => new FamilyForSwitchData(
            id: $family->id,
            name: $family->name,
            member_names: $family->members->pluck('name')->toArray(),
        ));

        return Inertia::render('MyPage/FamilySwitch', FamilySwitchResult::from([
            'families' => $familiesData->values(),
            'current_family_id' => $this->currentFamilyService->getCurrentFamilyId(),
        ]));
    }

    public function switch(Family $family): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->currentFamilyService->userBelongsToFamily($user->id, $family->id)) {
            abort(403);
        }

        $this->currentFamilyService->setCurrentFamily($family->id);

        return redirect()->route('home');
    }
}

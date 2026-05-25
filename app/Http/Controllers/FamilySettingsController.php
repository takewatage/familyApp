<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilySettingsResult;
use App\Dtos\Family\RegenerateFamilyCodeRequest;
use App\Dtos\Family\UpdateFamilySettingsRequest;
use App\Dtos\Model\FamilyData;
use App\Models\Family;
use App\Services\CurrentFamilyService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FamilySettingsController extends Controller
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

        return Inertia::render('MyPage/FamilySettings', FamilySettingsResult::from([
            'family' => $family->toArray(),
            'is_owner' => $family->owner_id === auth()->id(),
        ]));
    }

    public function update(UpdateFamilySettingsRequest $data): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        Gate::authorize('update', $family);

        $family->update([
            'name' => $data->name,
            'max_members' => $data->max_members,
        ]);

        return back()->with('message', '家族設定を更新しました');
    }

    public function regenerateCode(RegenerateFamilyCodeRequest $data): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        Gate::authorize('regenerateCode', $family);

        $family->update([
            'code' => Family::generateUniqueCode(),
            'code_expires_at' => $data->expires_at ? Carbon::parse($data->expires_at) : null,
        ]);

        return back()->with('message', '家族コードを再生成しました');
    }
}

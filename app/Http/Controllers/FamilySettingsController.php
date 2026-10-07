<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilySettingsResult;
use App\Dtos\Family\RegenerateFamilyCodeRequest;
use App\Dtos\Family\UpdateFamilyPwaSettingsRequest;
use App\Dtos\Family\UpdateFamilySettingsRequest;
use App\Dtos\Model\FamilyData;
use App\Models\Family;
use App\Services\CurrentFamilyService;
use App\Services\ImageUploadService;
use App\Services\PwaManifestService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FamilySettingsController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly PwaManifestService $pwaManifestService,
        private readonly ImageUploadService $imageService,
    )
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
            'pwa' => $this->pwaManifestService->resolve($family),
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

    /**
     * PWA 外観（ホーム画面のアプリ名・アプリ画像）を更新
     */
    public function updatePwa(UpdateFamilyPwaSettingsRequest $data): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        Gate::authorize('update', $family);

        $pwa = $family->settings['pwa'] ?? [];
        $pwa['name'] = $data->name;
        $oldIcon = null;

        if ($data->icon) {
            $oldIcon = $pwa['icon'] ?? null;
            $pwa['icon'] = $this->imageService->uploadAppIcon(
                $data->icon,
                "familyApp/{$family->id}/pwa-icon",
                config('pwa.background_color'),
            );
        }

        try {
            $this->savePwaSettings($family, $pwa);
        } catch (\Throwable $e) {
            // 保存に失敗したら今回アップロードした画像を残さない
            if ($data->icon) {
                $this->imageService->deleteQuietly($pwa['icon']['external_ids']);
            }

            throw $e;
        }

        // 保存が成功してから旧画像を削除する（削除に失敗しても保存結果は成功として返す）
        if ($oldIcon) {
            $this->imageService->deleteQuietly($oldIcon['external_ids'] ?? []);
        }

        return back()->with('message', 'アプリ設定を更新しました');
    }

    /**
     * PWA のアプリ画像を削除し、既定のアイコンに戻す
     */
    public function destroyPwaIcon(): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        Gate::authorize('update', $family);

        $pwa = $family->settings['pwa'] ?? [];
        $oldIcon = $pwa['icon'] ?? null;

        if ($oldIcon) {
            unset($pwa['icon']);
            $this->savePwaSettings($family, $pwa);
            $this->imageService->deleteQuietly($oldIcon['external_ids'] ?? []);
        }

        return back()->with('message', 'アプリ画像を削除しました');
    }

    private function savePwaSettings(Family $family, array $pwa): void
    {
        $pwa['updated_at'] = now()->toIso8601String();

        $settings = $family->settings ?? [];
        $settings['pwa'] = $pwa;

        $family->update(['settings' => $settings]);
    }
}

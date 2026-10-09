<?php

namespace App\Http\Controllers;

use App\Dtos\Family\FamilySettingsResult;
use App\Dtos\Family\RegenerateFamilyCodeRequest;
use App\Dtos\Family\UpdateFamilyBannerRequest;
use App\Dtos\Family\UpdateFamilyIconRequest;
use App\Dtos\Family\UpdateFamilyPwaSettingsRequest;
use App\Dtos\Family\UpdateFamilySettingsRequest;
use App\Dtos\Model\FamilyData;
use App\Models\Family;
use App\Services\CurrentFamilyService;
use App\Services\ImageUploadService;
use App\Services\PwaManifestService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FamilySettingsController extends Controller
{
    /** バナー画像の横幅（px）。横長画像をこの幅に縮小して WebP で保存する */
    private const BANNER_WIDTH = 1200;

    /** 家族のアイコンの横幅（px）。正方形を想定し、この幅に縮小して WebP で保存する */
    private const ICON_WIDTH = 256;

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
            'banner_url' => $family->settings['banner']['url'] ?? null,
            'icon_url' => $family->settings['icon']['url'] ?? null,
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

    /**
     * バナー画像（ホームのヒーローの背景）を更新
     */
    public function updateBanner(UpdateFamilyBannerRequest $data): RedirectResponse
    {
        return $this->updateImageSetting('banner', $data->banner, self::BANNER_WIDTH, 'バナーを更新しました');
    }

    /**
     * バナー画像を削除する
     */
    public function destroyBanner(): RedirectResponse
    {
        return $this->destroyImageSetting('banner', 'バナーを削除しました');
    }

    /**
     * 家族のアイコン（サイドメニューの家族名の横に表示）を更新
     */
    public function updateIcon(UpdateFamilyIconRequest $data): RedirectResponse
    {
        return $this->updateImageSetting('icon', $data->icon, self::ICON_WIDTH, 'アイコンを更新しました');
    }

    /**
     * 家族のアイコンを削除する
     */
    public function destroyIcon(): RedirectResponse
    {
        return $this->destroyImageSetting('icon', 'アイコンを削除しました');
    }

    /**
     * 家族設定の画像（settings.{key} = { external_id, url, updated_at }）をアップロードして差し替える（オーナーのみ）
     *
     * 新しい画像の保存が成功してから旧画像を削除する。保存に失敗したら今回アップロードした画像を消す。
     */
    private function updateImageSetting(string $key, UploadedFile $file, int $width, string $message): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        Gate::authorize('update', $family);

        $old = $family->settings[$key] ?? null;
        $uploaded = $this->imageService->upload($file, $width, storagePath: "familyApp/{$family->id}/{$key}");

        try {
            $this->saveSettings($family, $key, [
                'external_id' => $uploaded['external_id'],
                'url' => $uploaded['direct_url'],
                'updated_at' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->imageService->deleteQuietly([$uploaded['external_id']]);

            throw $e;
        }

        // 削除に失敗しても保存結果は成功として返す
        if ($old) {
            $this->imageService->deleteQuietly([$old['external_id']]);
        }

        return back()->with('message', $message);
    }

    /**
     * 家族設定の画像を削除する（オーナーのみ）
     */
    private function destroyImageSetting(string $key, string $message): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        Gate::authorize('update', $family);

        $old = $family->settings[$key] ?? null;

        if ($old) {
            $this->saveSettings($family, $key, null);
            $this->imageService->deleteQuietly([$old['external_id']]);
        }

        return back()->with('message', $message);
    }

    /**
     * 家族設定（settings JSON）の 1 項目を更新する（null なら項目ごと消す。他の項目は残す）
     */
    private function saveSettings(Family $family, string $key, ?array $value): void
    {
        $settings = $family->settings ?? [];

        if ($value === null) {
            unset($settings[$key]);
        } else {
            $settings[$key] = $value;
        }

        $family->update(['settings' => $settings]);
    }

    private function savePwaSettings(Family $family, array $pwa): void
    {
        $pwa['updated_at'] = now()->toIso8601String();

        $settings = $family->settings ?? [];
        $settings['pwa'] = $pwa;

        $family->update(['settings' => $settings]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Dtos\MyPage\MyPageData;
use App\Dtos\MyPage\SaveProfileData;
use App\Dtos\MyPage\UpdateSettingsRequest;
use App\Dtos\MyPage\UserSettingsResult;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\Lazy;

class MyPageController extends Controller
{
    public function __construct(
        private ImageUploadService $imageService,
    )
    {
    }

    public function index(Request $request): Response
    {
        $user = $request->user()->load(['families']);

        return Inertia::render('MyPage/index', MyPageData::from([
            'user' => $user->setAppends(['avatar']),
        ]));
    }

    public function settingsIndex(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('MyPage/UserSettings', UserSettingsResult::fromArray($user->settings ?? []));
    }

    public function updateProfile(SaveProfileData $data): RedirectResponse
    {
        $user = auth()->user();
        $user->update(['name' => $data->name, 'birthday' => $data->birthday]);
        if ($data->avatar_image) {
            $old = $user->files()->where('collection', 'avatar')->first();

            if ($old) {
                $this->imageService->delete($old->external_id);
                $old->delete();
            }

            $result = $this->imageService->upload($data->avatar_image, 400);

            $user->files()->create([
                'collection' => 'avatar',
                'path' => $result['external_id'],
                'url' => $result['direct_url'],
                'name' => $data->avatar_image->getClientOriginalName(),
                'mime_type' => 'image/webp',
                'sort' => 0,
            ]);
        }

        return back()->with('message', 'プロフィールを更新しました');
    }

    public function updateSettings(UpdateSettingsRequest $data): RedirectResponse
    {
        $user = auth()->user();
        $user->settings = [
            'theme' => $data->theme,
            'theme_color' => $data->theme_color,
        ];
        $user->save();

        return back()->with('message', '設定を保存しました');
    }
}

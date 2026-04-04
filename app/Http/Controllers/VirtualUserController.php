<?php

namespace App\Http\Controllers;

use App\Dtos\Family\SaveVirtualUserRequest;
use App\Models\VirtualUser;
use App\Services\CurrentFamilyService;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;

class VirtualUserController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
        private readonly ImageUploadService $imageService,
    ) {
    }

    public function store(SaveVirtualUserRequest $data): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family) {
            return redirect()->route('home');
        }

        $virtualUser = $family->virtualUsers()->create(['name' => $data->name]);

        if ($data->avatar_image) {
            $this->uploadAvatar($virtualUser, $data->avatar_image);
        }

        return back()->with('message', '仮想メンバーを追加しました');
    }

    public function update(VirtualUser $virtualUser, SaveVirtualUserRequest $data): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family || $virtualUser->family_id !== $family->id) {
            abort(403);
        }

        $virtualUser->update(['name' => $data->name]);

        if ($data->avatar_image) {
            $old = $virtualUser->files()->where('collection', 'avatar')->first();
            if ($old) {
                $this->imageService->delete($old->external_id);
                $old->delete();
            }
            $this->uploadAvatar($virtualUser, $data->avatar_image);
        }

        return back()->with('message', '仮想メンバーを更新しました');
    }

    public function destroy(VirtualUser $virtualUser): RedirectResponse
    {
        $family = $this->currentFamilyService->getCurrentFamily();

        if (!$family || $virtualUser->family_id !== $family->id) {
            abort(403);
        }

        $old = $virtualUser->files()->where('collection', 'avatar')->first();
        if ($old) {
            $this->imageService->delete($old->external_id);
            $old->delete();
        }

        $virtualUser->delete();

        return back()->with('message', '仮想メンバーを削除しました');
    }

    private function uploadAvatar(VirtualUser $virtualUser, $image): void
    {
        $result = $this->imageService->upload($image, 400);

        $virtualUser->files()->create([
            'collection' => 'avatar',
            'path' => $result['external_id'],
            'url' => $result['direct_url'],
            'name' => $image->getClientOriginalName(),
            'mime_type' => 'image/webp',
            'sort' => 0,
        ]);
    }
}

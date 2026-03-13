<?php

namespace App\Http\Controllers;

use App\Dtos\MyPage\MyPageData;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyPageController extends Controller
{
    public function __construct(
        private ImageUploadService $imageService,
    )
    {
    }

    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $user = User::query()->with(['files', 'families'])->findOrFail($userId);
        return Inertia::render('MyPage/index', MyPageData::from([
            'user' => $user,
        ]));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $user = $request->user();
        $user->update(['name' => $request->name]);

        if ($request->hasFile('avatar')) {
            $old = $user->files()->where('collection', 'avatar')->first();

            if ($old) {
                $this->imageService->delete($old->external_id);
                $old->delete();
            }

            $result = $this->imageService->upload($request->file('avatar'), 400);

            $user->files()->create([
                'collection' => 'avatar',
                'external_id' => $result['external_id'],
                'direct_url' => $result['direct_url'],
                'name' => $request->file('avatar')->getClientOriginalName(),
                'mime_type' => 'image/webp',
                'sort' => 0,
            ]);
        }

        return redirect()->back();
    }
}

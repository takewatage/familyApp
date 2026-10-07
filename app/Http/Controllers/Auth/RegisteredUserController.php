<?php

namespace App\Http\Controllers\Auth;

use App\Dtos\Auth\RegisterPageResult;
use App\Exceptions\InvalidInviteException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FamilyProvisionService;
use App\Services\ImageUploadService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
        private readonly FamilyProvisionService $familyProvisionService,
    ) {}

    /**
     * 登録ページ表示。新規登録は招待経由のみ（有効な招待がなければログインページへ）。
     */
    public function create(): Response|RedirectResponse
    {
        try {
            $family = $this->familyProvisionService->requireValidInviteFamily();
        } catch (InvalidInviteException $e) {
            return $this->redirectWithoutInvite($e);
        }

        return Inertia::render('Auth/Register', RegisterPageResult::from([
            'family_name' => $family->name,
            'google_enabled' => filled(config('services.google.client_id')),
        ]));
    }

    /**
     * 登録処理。完了後に招待先の家族へ参加する。
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $family = $this->familyProvisionService->requireValidInviteFamily();
        } catch (InvalidInviteException $e) {
            return $this->redirectWithoutInvite($e);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'birthday' => ['nullable', 'date'],
            'avatar_image' => ['nullable', 'image', 'max:10240'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birthday' => $request->birthday,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->familyProvisionService->joinInvitedFamily($user, $family);

        if ($request->hasFile('avatar_image')) {
            $result = $this->imageService->upload($request->file('avatar_image'), 400, storagePath: "familyApp/{$family->id}/avatar");

            $user->files()->create([
                'collection' => 'avatar',
                'path' => $result['external_id'],
                'url' => $result['direct_url'],
                'name' => $request->file('avatar_image')->getClientOriginalName(),
                'mime_type' => 'image/webp',
                'sort' => 0,
            ]);
        }

        return redirect()->route('home');
    }

    /**
     * 有効な招待がない場合は、理由を添えてログインページへ戻す
     */
    private function redirectWithoutInvite(InvalidInviteException $e): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('status', $e->getMessage());
    }
}

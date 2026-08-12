<?php

namespace App\Http\Controllers\Auth;

use App\Dtos\Auth\RegisterPageResult;
use App\Http\Controllers\Controller;
use App\Models\Family;
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
     * 登録ページ表示。
     *
     * 招待コードがセッションにある場合は招待先の家族名を表示し、
     * ない場合は通常の新規登録として表示する（登録時に本人の家族を自動作成する）。
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', RegisterPageResult::from([
            'family_name' => $this->inviteFamilyName(),
            'google_enabled' => filled(config('services.google.client_id')),
        ]));
    }

    /**
     * 登録処理。完了後に招待先の家族へ参加、招待がなければ本人の家族を作成する。
     */
    public function store(Request $request): RedirectResponse
    {
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

        // 招待があれば招待先へ参加、なければ本人の家族を作成する
        $family = $this->familyProvisionService->provisionForNewUser($user);

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
     * 招待セッションが有効な場合、招待先の家族名を返す
     */
    private function inviteFamilyName(): ?string
    {
        $code = session('invite_family_code');

        if (!$code) {
            return null;
        }

        $family = Family::where('code', $code)->first();

        if (!$family || ($family->code_expires_at && $family->code_expires_at->isPast())) {
            return null;
        }

        return $family->name;
    }
}

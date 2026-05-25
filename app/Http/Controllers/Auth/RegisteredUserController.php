<?php

namespace App\Http\Controllers\Auth;

use App\Dtos\Auth\RegisterPageResult;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\User;
use App\Services\CurrentFamilyService;
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
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    /**
     * 登録ページ表示。招待コードがセッションにない場合はログインページへ。
     */
    public function create(): Response|RedirectResponse
    {
        $code = session('invite_family_code');

        if (!$code) {
            return redirect()->route('login');
        }

        $family = Family::where('code', $code)->first();

        if (!$family || ($family->code_expires_at && $family->code_expires_at->isPast())) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/Register', RegisterPageResult::from([
            'family_name' => $family->name,
        ]));
    }

    /**
     * 登録処理。完了後に対象の家族へ自動参加する。
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

        $inviteCode = session('invite_family_code');
        $inviteFamily = $inviteCode ? Family::where('code', $inviteCode)->first() : null;
        $familyId = $inviteFamily?->id ?? 'unassigned';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birthday' => $request->birthday,
        ]);

        if ($request->hasFile('avatar_image')) {
            $result = $this->imageService->upload($request->file('avatar_image'), 400, storagePath: "familyApp/{$familyId}/avatar");

            $user->files()->create([
                'collection' => 'avatar',
                'path' => $result['external_id'],
                'url' => $result['direct_url'],
                'name' => $request->file('avatar_image')->getClientOriginalName(),
                'mime_type' => 'image/webp',
                'sort' => 0,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        // セッションのコードで家族に自動参加
        if ($inviteFamily && !($inviteFamily->code_expires_at && $inviteFamily->code_expires_at->isPast())) {
            $role = in_array(session('invite_role'), ['parent', 'child', 'guest'])
                ? session('invite_role')
                : 'guest';

            $inviteFamily->members()->attach($user->id, ['role' => $role]);
            $this->currentFamilyService->setCurrentFamily($inviteFamily->id);
        }

        session()->forget(['invite_family_code', 'invite_role']);

        return redirect()->route('home');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvalidInviteException;
use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Services\CurrentFamilyService;
use App\Services\FamilyProvisionService;
use App\Services\SocialAuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService,
        private readonly FamilyProvisionService $familyProvisionService,
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    /**
     * Googleの認可画面へリダイレクトする
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Googleからのコールバックを処理する
     */
    public function callback(Request $request): RedirectResponse
    {
        // 同意画面でキャンセルされた場合
        if ($request->has('error')) {
            return redirect()->route('login')->withErrors(['email' => 'Googleログインがキャンセルされました。']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Googleログインに失敗しました。時間をおいて再度お試しください。']);
        }

        try {
            $user = $this->socialAuthService->findOrLinkGoogleUser($googleUser);
        } catch (SocialAuthException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        $isNew = $user === null;

        if ($isNew) {
            // 新規登録は招待経由のみ許可する
            try {
                $inviteFamily = $this->familyProvisionService->requireValidInviteFamily();
            } catch (InvalidInviteException $e) {
                return redirect()
                    ->route('login')
                    ->withErrors(['email' => 'このGoogleアカウントは登録されていません。' . $e->getMessage()]);
            }

            $user = $this->socialAuthService->createGoogleUser($googleUser);
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        if ($isNew) {
            event(new Registered($user));

            $this->familyProvisionService->joinInvitedFamily($user, $inviteFamily);
        } else {
            $this->currentFamilyService->resolveAndSetForUser($user);
        }

        return redirect()->intended(route('home', absolute: false));
    }
}

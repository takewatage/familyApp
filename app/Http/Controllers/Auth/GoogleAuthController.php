<?php

namespace App\Http\Controllers\Auth;

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
            [$user, $isNew] = $this->socialAuthService->findOrCreateGoogleUser($googleUser);
        } catch (SocialAuthException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        if ($isNew) {
            event(new Registered($user));

            // 招待セッションがあれば招待先へ参加、なければ本人の家族を作成する
            $this->familyProvisionService->provisionForNewUser($user);
        } else {
            $this->currentFamilyService->resolveAndSetForUser($user);
        }

        return redirect()->intended(route('home', absolute: false));
    }
}

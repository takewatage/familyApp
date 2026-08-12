<?php

namespace App\Http\Controllers\Auth;

use App\Dtos\Auth\LoginPageResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CurrentFamilyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyService $currentFamilyService,
    ) {}

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $dto = LoginPageResult::from([
            'can_reset_password' => Route::has('password.request'),
            'google_enabled' => filled(config('services.google.client_id')),
            'status' => session('status'),
        ]);

        return Inertia::render('Auth/Login', $dto->toArray());
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // 家族コードに代わり、所属家族から現在の家族を決定する
        $this->currentFamilyService->resolveAndSetForUser($request->user());

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

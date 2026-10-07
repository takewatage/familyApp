<?php

namespace App\Providers;

use App\Services\CurrentFamilyService;
use App\Services\PwaManifestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // app.blade.php の <head> に PWA（manifest / apple-touch-icon / アプリ名）を出力する
        View::composer('app', function ($view) {
            $family = Auth::check() ? app(CurrentFamilyService::class)->getCurrentFamily() : null;
            $view->with('pwa', app(PwaManifestService::class)->resolve($family));
        });
    }
}

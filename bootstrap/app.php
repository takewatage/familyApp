<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // E2E テスト（Playwright）用のテストデータ作成ルート。APP_ENV=e2e のときだけ登録する
            if (app()->environment('e2e')) {
                Route::prefix('__e2e')->group(__DIR__ . '/../routes/e2e.php');
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(
            append: [
                \App\Http\Middleware\HandleInertiaRequests::class,
                \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            ]
        );

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

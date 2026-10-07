<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServiceWorkerController extends Controller
{
    /**
     * ビルド済みの Service Worker をサイト直下（/sw.js）から配信する。
     * 未ビルド（sail yarn dev 中など）の場合は 404。
     */
    public function show(): BinaryFileResponse
    {
        $path = config('pwa.service_worker_path');

        abort_unless(is_string($path) && is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}

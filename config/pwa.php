<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Service Worker
    |--------------------------------------------------------------------------
    |
    | vite-plugin-pwa が public/build に出力する Service Worker。
    | GET /sw.js（ServiceWorkerController）で配信し、スコープを / にする。
    |
    */
    'service_worker_path' => public_path('build/sw.js'),

    /*
    |--------------------------------------------------------------------------
    | Web App Manifest の既定値
    |--------------------------------------------------------------------------
    |
    | 家族設定（families.settings.pwa）が未設定の項目はこの値を使う。
    | theme_color は固定（ユーザーごとのテーマ色は <meta name="theme-color"> 側で反映する）。
    |
    */
    'default_name' => 'familyApp',

    'default_icons' => [
        'apple' => '/icons/apple-touch-icon.png',
        '192' => '/icons/icon-192x192.png',
        '512' => '/icons/icon-512x512.png',
        'maskable' => '/icons/icon-512x512.png',
    ],

    'theme_color' => '#FF45CE',

    'background_color' => '#fdf8ee',

    'start_url' => '/home',
];

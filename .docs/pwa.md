# PWA実装ガイド — 家計簿Webアプリ

> **技術スタック:** Laravel 12 / Inertia / Vue 3 / Vite

---

## 目次

1. [vite-plugin-pwa の導入](#1-vite-plugin-pwa-の導入)
2. [Web App Manifest の設定](#2-web-app-manifest-の設定)
3. [Service Worker のキャッシュ戦略](#3-service-worker-のキャッシュ戦略)
4. [バージョン更新の通知UI（Vue コンポーネント）](#4-バージョン更新の通知uivue-コンポーネント)
5. [認証セッションの長期化](#5-認証セッションの長期化)
6. [Laravelサーバー側の設定](#6-laravelサーバー側の設定)
7. [オフラインフォールバックページ](#7-オフラインフォールバックページ)
8. [動作確認・デバッグ](#8-動作確認・デバッグ)
9. [本番デプロイ時のチェックリスト](#9-本番デプロイ時のチェックリスト)

---

## 1. vite-plugin-pwa の導入

### 1-1. パッケージインストール

```bash
npm install -D vite-plugin-pwa
```

### 1-2. vite.config.js の設定

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        VitePWA({
            // ユーザーに更新を確認してからSWを切り替える
            registerType: 'prompt',

            // ビルド出力先（Laravelのpublicに合わせる）
            outDir: 'public',

            // manifest の設定（後述）
            manifest: false, // manifest.webmanifest を自分で用意する場合
            // manifest: { ... } で直接書いてもOK（Step 2 参照）

            workbox: {
                // プリキャッシュ対象（Viteビルド出力のアセット）
                globPatterns: [
                    'build/assets/**/*.{js,css,ico,png,svg,woff2}',
                ],

                // ランタイムキャッシュ（APIやナビゲーション）
                runtimeCaching: [
                    // ① APIレスポンス — Network First
                    {
                        urlPattern: /^https:\/\/your-app\.com\/api\/.*/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24, // 1日
                            },
                            networkTimeoutSeconds: 5,
                        },
                    },
                    // ② 画像（アップロード画像等）— Cache First
                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'image-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 * 30, // 30日
                            },
                        },
                    },
                    // ③ フォント — Cache First
                    {
                        urlPattern: /\.(?:woff|woff2|ttf|otf)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'font-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365, // 1年
                            },
                        },
                    },
                ],

                // オフライン用フォールバック
                navigateFallback: '/offline',
                navigateFallbackDenylist: [
                    /^\/api\//,       // APIはフォールバックしない
                    /^\/sanctum\//,
                ],
            },
        }),
    ],
});
```

---

## 2. Web App Manifest の設定

### 2-1. manifest.webmanifest を作成

`public/manifest.webmanifest` に配置する。

```json
{
    "name": "家計簿アプリ",
    "short_name": "家計簿",
    "description": "シンプルな家計簿Webアプリ",
    "start_url": "/",
    "scope": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "#4f46e5",
    "orientation": "portrait-primary",
    "icons": [
        {
            "src": "/icons/icon-192x192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "/icons/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png"
        },
        {
            "src": "/icons/icon-512x512.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose": "maskable"
        }
    ]
}
```

> **Tips:** `vite.config.js` 内の `manifest: { ... }` に直接書くと、`vite-plugin-pwa`
> がビルド時に自動生成してくれるので、手動ファイル管理が不要になる。その場合は `manifest: false`
> を削除し、上記JSONの内容をオブジェクトとして渡す。

### 2-2. アイコンの準備

```
public/icons/
  ├── icon-192x192.png
  ├── icon-512x512.png
  └── apple-touch-icon.png   （180x180推奨）
```

### 2-3. Bladeテンプレートに追加

`resources/views/app.blade.php`（Inertiaのルートテンプレート）にメタタグを追加する。

```html

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
```

---

## 3. Service Worker のキャッシュ戦略

`vite-plugin-pwa` がWorkboxを使って自動生成するので、基本は `vite.config.js` の設定で完結する。以下は戦略の整理。

| 対象                | 戦略                           | 理由                         |
|-------------------|------------------------------|----------------------------|
| JS / CSS（Viteビルド） | **Precache**                 | ハッシュ付きファイル名。ビルドごとに自動更新     |
| API (`/api/*`)    | **Network First**            | 常に最新データを優先。オフライン時のみキャッシュ返却 |
| 画像                | **Cache First**              | 変更頻度が低い。30日で有効期限切れ         |
| フォント              | **Cache First**              | ほぼ不変。1年キャッシュ               |
| HTMLナビゲーション       | **Network First + Fallback** | オフライン時は `/offline` ページを返す  |
| 認証関連 (`/login` 等) | **キャッシュしない**                 | セッション/CSRFに依存するためキャッシュ不適   |

---

## 4. バージョン更新の通知UI（Vue コンポーネント）

`vite-plugin-pwa` が提供する `virtual:pwa-register/vue` を使う。

### 4-1. コンポーネント作成

`resources/js/Components/PwaUpdatePrompt.vue`

```vue

<script setup>
    import { useRegisterSW } from 'virtual:pwa-register/vue';

    const {
        needRefresh,    // 新バージョン検知時に true になる
        updateServiceWorker,
    } = useRegisterSW({
        onRegisteredSW (swUrl, registration) {
            // 定期的に更新をチェック（1時間ごと）
            if (registration) {
                setInterval(() => {
                    registration.update();
                }, 60 * 60 * 1000);
            }
        },
        onRegisterError (error) {
            console.error('SW registration error:', error);
        },
    });

    function onUpdate () {
        updateServiceWorker();
    }

    function onDismiss () {
        needRefresh.value = false;
    }
</script>

<template>
    <Transition name="slide-up">
        <div
            v-if="needRefresh"
            class="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96
             bg-indigo-600 text-white rounded-xl shadow-2xl p-4
             flex items-center justify-between z-50"
        >
            <div>
                <p class="font-bold text-sm">アプリが更新されました</p>
                <p class="text-xs text-indigo-200 mt-0.5">最新版に更新してください</p>
            </div>
            <div class="flex gap-2 ml-4">
                <button
                    @click="onDismiss"
                    class="text-xs text-indigo-200 hover:text-white px-2 py-1"
                >
                    あとで
                </button>
                <button
                    @click="onUpdate"
                    class="bg-white text-indigo-600 font-bold text-sm px-4 py-1.5 rounded-lg
                 hover:bg-indigo-50 transition"
                >
                    更新する
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
    .slide-up-enter-active,
    .slide-up-leave-active {
        transition: all 0.3s ease;
    }

    .slide-up-enter-from,
    .slide-up-leave-to {
        transform: translateY(100%);
        opacity: 0;
    }
</style>
```

### 4-2. レイアウトに配置

`resources/js/Layouts/AppLayout.vue` などに追加する。

```vue

<script setup>
    import PwaUpdatePrompt from '@/Components/PwaUpdatePrompt.vue';
</script>

<template>
    <div>
        <slot/>
        <PwaUpdatePrompt/>
    </div>
</template>
```

---

## 5. 認証セッションの長期化

「一度ログインしたらしばらく不要」はPWAキャッシュではなく、サーバー側のセッション設計で実現する。

### 5-1. セッション寿命の延長

`config/session.php`

```php
return [
    'lifetime' => 43200,         // 30日（分単位: 60 * 24 * 30）
    'expire_on_close' => false,  // ブラウザを閉じても維持
];
```

### 5-2. Remember Me をデフォルト有効にする

`resources/js/Pages/Auth/Login.vue`（Breezeなどの認証スカフォールド前提）

```vue

<script setup>
    import { useForm } from '@inertiajs/vue3';

    const form = useForm({
        email: '',
        password: '',
        remember: true, // ← デフォルトでON
    });
</script>
```

> **補足:** Remember
> Meを有効にすると、LaravelはセッションCookieとは別にrememberトークンをCookieに保存する。セッションが切れても、このトークンで自動的に再認証される。家計簿アプリは個人利用が主なので、デフォルトONで問題ない。

### 5-3. Sanctum（API認証）使用時の追加設定

SPA認証にSanctumを使っている場合：

`config/sanctum.php`

```php
return [
    'expiration' => null, // トークンを無期限にする（またはnullで期限なし）
];
```

`.env`

```
SESSION_LIFETIME=43200
SANCTUM_STATEFUL_DOMAINS=your-app.com
```

---

## 6. Laravelサーバー側の設定

### 6-1. HTTPS の強制

PWAはHTTPS必須（localhost除く）。

`app/Providers/AppServiceProvider.php`

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

### 6-2. Service Worker スコープのルーティング

Service Workerの `sw.js` はルートスコープで配信する必要がある。Viteのビルド出力は `public/build/` 配下になるため、
`vite-plugin-pwa` に `outDir: 'public'` を指定することで `public/sw.js` として出力される。

特にLaravelのルーティングで `/sw.js` がキャッチされないように確認する。通常はpublicディレクトリの静的ファイルが優先されるので問題ない。

### 6-3. キャッシュ制御ヘッダー

`sw.js` 自体はブラウザにキャッシュさせない。Nginxの設定例：

```nginx
location = /sw.js {
    add_header Cache-Control "no-cache, no-store, must-revalidate";
    add_header Pragma "no-cache";
    expires 0;
}

location /build/assets/ {
    add_header Cache-Control "public, max-age=31536000, immutable";
}
```

---

## 7. オフラインフォールバックページ

### 7-1. オフラインルート追加

`routes/web.php`

```php
Route::get('/offline', fn () => inertia('Offline'));
```

### 7-2. Vueページ作成

`resources/js/Pages/Offline.vue`

```vue

<script setup>
    import { ref, onMounted } from 'vue';

    const isOnline = ref(navigator.onLine);

    onMounted(() => {
        window.addEventListener('online', () => {
            isOnline.value = true;
            // 復帰したら自動リロード
            window.location.reload();
        });
        window.addEventListener('offline', () => {
            isOnline.value = false;
        });
    });
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <div class="text-center">
            <div class="text-6xl mb-4">📡</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                オフラインです
            </h1>
            <p class="text-gray-600 mb-6">
                インターネット接続が回復したら自動的に復帰します。
            </p>
            <button
                @click="() => window.location.reload()"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg
               hover:bg-indigo-700 transition"
            >
                再読み込み
            </button>
        </div>
    </div>
</template>
```

---

## 8. 動作確認・デバッグ

### 8-1. ビルド＆確認コマンド

```bash
# 開発時（SWは動作しない。ホットリロード優先）
npm run dev

# PWA動作確認用ビルド
npm run build

# Laravelサーバー起動して確認
php artisan serve
```

### 8-2. Chrome DevToolsでの確認

1. **Application → Manifest** : マニフェストが正しく読み込まれているか
2. **Application → Service Workers** : SW が登録されているか、ステータス確認
3. **Application → Cache Storage** : 各キャッシュの中身を確認
4. **Network → Offline チェック** : オフラインフォールバックの動作確認

### 8-3. Lighthouse監査

```
Chrome DevTools → Lighthouse → PWA カテゴリにチェック → Analyze
```

目標：PWAバッジの取得（installable, reliable, fast）

---

## ファイル構成まとめ

```
├── public/
│   ├── manifest.webmanifest          ← マニフェスト
│   ├── sw.js                         ← Vite PWAが自動生成
│   ├── workbox-*.js                  ← Vite PWAが自動生成
│   └── icons/
│       ├── icon-192x192.png
│       ├── icon-512x512.png
│       └── apple-touch-icon.png
│
├── resources/
│   ├── views/
│   │   └── app.blade.php            ← manifest link追加
│   └── js/
│       ├── Components/
│       │   └── PwaUpdatePrompt.vue   ← 更新通知UI
│       ├── Layouts/
│       │   └── AppLayout.vue         ← PwaUpdatePrompt配置
│       └── Pages/
│           └── Offline.vue           ← オフラインページ
│
├── config/
│   ├── session.php                   ← lifetime: 43200
│   └── sanctum.php                   ← expiration設定
│
├── routes/
│   └── web.php                       ← /offline ルート追加
│
└── vite.config.js                    ← VitePWA プラグイン設定
```

import { defineConfig, devices } from '@playwright/test'

/**
 * E2E テスト（Playwright）
 *
 * Sail コンテナ内で実行する: `sail yarn e2e`
 * - E2E 専用 DB（testing_e2e）を使うため、開発 DB（familyApp）には影響しない
 * - テスト用サーバーは APP_ENV=e2e で `php artisan serve` を別ポートで起動する
 * - 画面は `public/build` のビルド済みアセットを使う（事前に `sail npm run build`）
 */
const PORT = 8010
export const BASE_URL = `http://127.0.0.1:${PORT}`

/** テスト用サーバー・マイグレーションで共通の環境変数（.env の値を上書き） */
export const E2E_ENV = {
    APP_ENV: 'e2e',
    APP_URL: BASE_URL,
    DB_DATABASE: 'testing_e2e',
    SESSION_DRIVER: 'database',
    CACHE_STORE: 'database',
    QUEUE_CONNECTION: 'sync',
    BROADCAST_CONNECTION: 'log',
    MAIL_MAILER: 'log',
    // Google ログインは E2E 対象外（実アカウントが必要なため Feature テストで担保）
    GOOGLE_CLIENT_ID: '',
}

export default defineConfig({
    testDir: './e2e',
    globalSetup: './e2e/global-setup.ts',
    // 全テストで 1 つの DB を共有するため直列実行（データはテストごとに一意に作る）
    workers: 1,
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    reporter: [
        ['list'],
        ['html', { open: 'never', outputFolder: 'e2e/.report' }],
    ],
    outputDir: 'e2e/.results',
    use: {
        baseURL: BASE_URL,
        locale: 'ja-JP',
        timezoneId: 'Asia/Tokyo',
        // PWA の Service Worker のキャッシュがテスト結果に影響しないようにする
        serviceWorkers: 'block',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'mobile', use: { ...devices['Pixel 7'] } }],
    webServer: {
        // --no-reload: 上書きした環境変数をそのまま PHP サーバーへ渡すため
        command: `php artisan serve --host=127.0.0.1 --port=${PORT} --no-reload`,
        url: `${BASE_URL}/up`,
        env: E2E_ENV,
        reuseExistingServer: false,
        timeout: 60_000,
    },
})

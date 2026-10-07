import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import { VitePWA } from 'vite-plugin-pwa'
import { readFileSync } from 'node:fs'
import { execSync } from 'node:child_process'

// バージョン番号の SSoT は package.json の version（リリース時に npm version patch|minor|major --no-git-tag-version で更新）
const pkg = JSON.parse(readFileSync(new URL('./package.json', import.meta.url), 'utf-8'))

// ビルド識別子。CI 等では APP_BUILD_ID で上書きする
const buildId =
    process.env.APP_BUILD_ID ??
    (() => {
        try {
            return execSync('git rev-parse --short HEAD', { stdio: ['ignore', 'pipe', 'ignore'] }).toString().trim()
        } catch {
            return 'unknown'
        }
    })()

export default defineConfig({
    define: {
        __APP_VERSION__: JSON.stringify(pkg.version),
        __APP_BUILD_ID__: JSON.stringify(buildId),
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        vuetify({ autoImport: true, styles: 'sass' }),
        VitePWA({
            registerType: 'prompt',
            // 登録は usePwaUpdate（virtual:pwa-register/vue）から行う
            injectRegister: false,
            // manifest は Laravel ルート /manifest.webmanifest で家族設定から動的生成する
            manifest: false,
            // 生成物は gitignore 済みの public/build に出力し、Laravel ルート /sw.js で配信する
            outDir: 'public/build',
            // 登録 URL（buildBase + filename）を /sw.js にする
            buildBase: '/',
            scope: '/',
            base: '/',
            devOptions: { enabled: false },
            workbox: {
                globPatterns: ['assets/**/*.{js,css,woff2,png,svg,ico}'],
                // SW は /sw.js から配信されるため、public/build からの相対 URL を /build/assets/... に補正する
                modifyURLPrefix: { 'assets/': '/build/assets/' },
                // ファイル名にハッシュを含むためリビジョン不要（modifyURLPrefix 適用後の URL で判定される）
                dontCacheBustURLsMatching: /^\/build\/assets\//,
                // Laravel は index.html を持たない・HTML はキャッシュしない
                navigateFallback: null,
                // /sw.js から配信すると相対パスの workbox-*.js を解決できないため sw.js に同梱する
                inlineWorkboxRuntime: true,
                runtimeCaching: [
                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'image-cache',
                            expiration: { maxEntries: 50, maxAgeSeconds: 2592000 },
                        },
                    },
                    {
                        urlPattern: /\.(?:woff|woff2|ttf|otf)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'font-cache',
                            expiration: { maxEntries: 10, maxAgeSeconds: 31536000 },
                        },
                    },
                ],
            },
        }),
    ],
})

import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
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
        vuetify({autoImport: true, styles: 'sass'}),
        VitePWA({
            registerType: 'prompt',
            outDir: 'public',
            manifest: {
                name: 'Family App',
                short_name: 'Family',
                description: 'ファミリー管理アプリ',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                background_color: '#fdf8ee',
                theme_color: '#ff45ce',
                orientation: 'portrait-primary',
                icons: [
                    { src: '/icons/icon-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globPatterns: ['build/assets/**/*.{js,css,ico,png,svg,woff2}'],
                runtimeCaching: [
                    {
                        urlPattern: /\/api\/.*/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                            expiration: { maxEntries: 100, maxAgeSeconds: 86400 },
                            networkTimeoutSeconds: 5,
                        },
                    },
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
                navigateFallback: '/offline',
                navigateFallbackDenylist: [/^\/api\//, /^\/sanctum\//],
            },
        }),
    ],
})

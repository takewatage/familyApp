import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import type { FamilyPwaSettingsData } from '@/Types/dto.generated'

/**
 * <head> の PWA 関連要素（manifest / apple-touch-icon / アプリ名）を現在の家族の設定に合わせる。
 * Inertia の SPA 遷移では Blade が再描画されないため、家族切り替え・設定変更時にクライアントで差し替える。
 */
export function usePwaMeta() {
    const page = usePage()

    function upsert<K extends 'link' | 'meta'>(tag: K, selector: string, init: (el: HTMLElementTagNameMap[K]) => void) {
        let el = document.head.querySelector<HTMLElementTagNameMap[K]>(selector)

        if (!el) {
            el = document.createElement(tag)
            document.head.appendChild(el)
        }

        init(el)
    }

    function apply(pwa: FamilyPwaSettingsData) {
        upsert('link', 'link[rel="manifest"]', (el) => {
            el.rel = 'manifest'
            el.crossOrigin = 'use-credentials'
            el.href = `/manifest.webmanifest?v=${pwa.manifestHash}`
        })
        upsert('link', 'link[rel="apple-touch-icon"]', (el) => {
            el.rel = 'apple-touch-icon'
            el.href = pwa.iconApple
        })
        upsert('meta', 'meta[name="apple-mobile-web-app-title"]', (el) => {
            el.name = 'apple-mobile-web-app-title'
            el.content = pwa.name
        })
    }

    watch(
        () => (page.props.pwa as FamilyPwaSettingsData | undefined)?.manifestHash,
        () => {
            const pwa = page.props.pwa as FamilyPwaSettingsData | undefined

            if (pwa) {
                apply(pwa)
            }
        },
        { immediate: true },
    )
}

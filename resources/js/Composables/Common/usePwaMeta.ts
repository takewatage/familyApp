import { router } from '@inertiajs/vue3'
import type { FamilyPwaSettingsData } from '@/Types/dto.generated'

let appliedHash: string | null = null

function upsert<K extends 'link' | 'meta'>(tag: K, selector: string, init: (el: HTMLElementTagNameMap[K]) => void) {
    let el = document.head.querySelector<HTMLElementTagNameMap[K]>(selector)

    if (!el) {
        el = document.createElement(tag)
        document.head.appendChild(el)
    }

    init(el)
}

function apply(pwa: FamilyPwaSettingsData | undefined) {
    if (!pwa || pwa.manifestHash === appliedHash) {
        return
    }

    appliedHash = pwa.manifestHash

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

/**
 * <head> の PWA 関連要素（manifest / apple-touch-icon / アプリ名）を共有プロパティ `pwa` に合わせる。
 * Inertia の SPA 遷移では Blade が再描画されないため、家族切り替え・設定変更・ログアウト時にクライアントで差し替える。
 * レイアウトに依存せず全ページで効かせるため、app.ts で一度だけ呼ぶ。
 */
export function setupPwaMeta(initialPwa: FamilyPwaSettingsData | undefined) {
    apply(initialPwa)

    router.on('navigate', (event) => {
        apply(event.detail.page.props.pwa as FamilyPwaSettingsData | undefined)
    })
}

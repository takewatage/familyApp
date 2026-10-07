import { computed, ref } from 'vue'

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform: string }>
}

export type PwaPlatform = 'ios' | 'android' | 'desktop' | 'other'

// beforeinstallprompt はページ読み込み直後に一度だけ発火するため、モジュール読み込み時に登録して取りこぼしを防ぐ
const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null)
const installed = ref(false)

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault()
        deferredPrompt.value = event as BeforeInstallPromptEvent
    })
    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null
        installed.value = true
    })
}

function detectPlatform(): PwaPlatform {
    const ua = navigator.userAgent

    // iPadOS 13+ の Safari はデスクトップ（Mac）の UA を返すため、タッチ対応で判定する
    const isIPadOS = /Macintosh/.test(ua) && navigator.maxTouchPoints > 1

    if (/iPhone|iPad|iPod/.test(ua) || isIPadOS) {
        return 'ios'
    }

    if (/Android/.test(ua)) {
        return 'android'
    }

    if (/Windows|Macintosh|Linux|CrOS/.test(ua)) {
        return 'desktop'
    }

    return 'other'
}

function detectStandalone(): boolean {
    const nav = navigator as Navigator & { standalone?: boolean }

    return window.matchMedia('(display-mode: standalone)').matches || nav.standalone === true
}

/**
 * PWA のインストール状態・インストール手段を判定する
 */
export function usePwaInstall() {
    const platform = detectPlatform()
    const isStandalone = computed(() => installed.value || detectStandalone())
    const canPrompt = computed(() => deferredPrompt.value !== null)

    async function prompt(): Promise<boolean> {
        const event = deferredPrompt.value

        if (!event) {
            return false
        }

        await event.prompt()
        const { outcome } = await event.userChoice
        deferredPrompt.value = null

        return outcome === 'accepted'
    }

    return { platform, isStandalone, canPrompt, prompt }
}

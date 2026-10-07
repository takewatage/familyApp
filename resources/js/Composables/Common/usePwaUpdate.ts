import { onUnmounted, ref } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'

// 公式の Periodic Service Worker Updates パターン（1 時間ごと）
const UPDATE_CHECK_INTERVAL_MS = 60 * 60 * 1000

/**
 * Service Worker（/sw.js）を登録し、新しいビルドの検知（needRefresh）を公開する。
 * 開発サーバー（sail yarn dev）では SW を登録しない。
 */
export function usePwaUpdate() {
    if (!import.meta.env.PROD) {
        return {
            needRefresh: ref(false),
            update: async () => {},
            dismiss: () => {},
        }
    }

    let registration: ServiceWorkerRegistration | undefined
    let intervalId: ReturnType<typeof setInterval> | undefined

    const checkForUpdate = () => {
        if (!registration || registration.installing || !navigator.onLine) {
            return
        }

        registration.update().catch(() => {})
    }

    const onVisibilityChange = () => {
        if (document.visibilityState === 'visible') {
            checkForUpdate()
        }
    }

    const { needRefresh, updateServiceWorker } = useRegisterSW({
        immediate: true,
        onRegisteredSW(_swUrl, r) {
            registration = r
            intervalId = setInterval(checkForUpdate, UPDATE_CHECK_INTERVAL_MS)
            document.addEventListener('visibilitychange', onVisibilityChange)
        },
    })

    onUnmounted(() => {
        if (intervalId) {
            clearInterval(intervalId)
        }

        document.removeEventListener('visibilitychange', onVisibilityChange)
    })

    // 新しい SW を有効化し、controlling イベントでページを再読み込みする
    const update = () => updateServiceWorker(true)

    // 「あとで」: 通知を閉じる（次回の更新チェックで新しいビルドがあれば再通知）
    const dismiss = () => {
        needRefresh.value = false
    }

    return { needRefresh, update, dismiss }
}

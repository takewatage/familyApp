import { ref } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'

// 公式の Periodic Service Worker Updates パターン（1 時間ごと）
const UPDATE_CHECK_INTERVAL_MS = 60 * 60 * 1000

// コンポーネントが先に参照しても同じ ref を共有するよう、差し替えずに値だけ更新する
const needRefresh = ref(false)
let updateServiceWorker: (reloadPage?: boolean) => Promise<void> = async () => {}
let started = false

/**
 * Service Worker（/sw.js）を登録し、新しいビルドの検知を開始する。
 * アプリ全体で一度だけ（app.ts から）呼ぶ。開発サーバー（sail yarn dev）では SW を登録しない。
 */
export function startPwaUpdate() {
    if (started || !import.meta.env.PROD) {
        return
    }

    started = true

    let registration: ServiceWorkerRegistration | undefined

    const checkForUpdate = () => {
        if (!registration || registration.installing || !navigator.onLine) {
            return
        }

        registration.update().catch(() => {})
    }

    const sw = useRegisterSW({
        immediate: true,
        onNeedRefresh() {
            needRefresh.value = true
        },
        onRegisteredSW(_swUrl, r) {
            registration = r
            setInterval(checkForUpdate, UPDATE_CHECK_INTERVAL_MS)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    checkForUpdate()
                }
            })
        },
    })

    updateServiceWorker = sw.updateServiceWorker
}

/**
 * 新しいビルドの検知状態（needRefresh）と操作を返す。登録は startPwaUpdate で済ませておくこと。
 */
export function usePwaUpdate() {
    // 新しい SW を有効化し、controlling イベントでページを再読み込みする
    const update = () => updateServiceWorker(true)

    // 「あとで」: 通知を閉じる（次回の更新チェックで新しいビルドがあれば再通知）
    const dismiss = () => {
        needRefresh.value = false
    }

    return { needRefresh, update, dismiss }
}

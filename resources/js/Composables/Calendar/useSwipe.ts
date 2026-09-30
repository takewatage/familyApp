// 横スワイプジェスチャーを扱うcomposable。
// transform: translateX による指追従と、しきい値判定によるページ送りを提供する。
// カレンダーに内包する想定だが、ロジックが独立しているので分離している。

import { ref, type Ref } from 'vue'

export interface UseSwipeOptions {
    /** スワイプ確定とみなす移動量の割合（ビューポート幅に対する比率）。デフォルト0.25 */
    threshold?: number
    /** 前へ送られたとき */
    onPrev: () => void
    /** 次へ送られたとき */
    onNext: () => void
    /** アニメーション時間(ms)。CSS側のtransitionと合わせる。デフォルト280 */
    duration?: number
}

export interface UseSwipeReturn {
    /** track要素に bind する style */
    trackStyle: Ref<Record<string, string>>
    /** アニメーション中フラグ（CSSクラス付与に使う） */
    isAnimating: Ref<boolean>
    onTouchStart: (e: TouchEvent | MouseEvent) => void
    onTouchMove: (e: TouchEvent | MouseEvent) => void
    onTouchEnd: () => void
    /** PC確認用のマウスドラッグ */
    onMouseDown: (e: MouseEvent) => void
}

function getPoint(e: TouchEvent | MouseEvent): { x: number; y: number } {
    if ('touches' in e && e.touches.length) {
        return { x: e.touches[0].clientX, y: e.touches[0].clientY }
    }

    const me = e as MouseEvent

    return { x: me.clientX, y: me.clientY }
}

export function useSwipe(options: UseSwipeOptions): UseSwipeReturn {
    const threshold = options.threshold ?? 0.25
    const duration = options.duration ?? 280

    const trackStyle = ref<Record<string, string>>({})
    const isAnimating = ref(false)

    let startX = 0
    let startY = 0
    let currentX = 0
    let dragging = false
    let axisLocked: 'x' | 'y' | null = null
    let viewportEl: HTMLElement | null = null

    function onTouchStart(e: TouchEvent | MouseEvent): void {
        const p = getPoint(e)

        startX = p.x
        startY = p.y
        currentX = 0
        dragging = true
        axisLocked = null
        isAnimating.value = false
        trackStyle.value = {}
        viewportEl = e.currentTarget as HTMLElement
    }

    function onTouchMove(e: TouchEvent | MouseEvent): void {
        if (!dragging) {
            return
        }

        const p = getPoint(e)
        const dx = p.x - startX
        const dy = p.y - startY

        // 最初の動きで縦横どちらのジェスチャーか確定（縦スクロールを妨げない）
        if (axisLocked === null) {
            if (Math.abs(dx) > 8 || Math.abs(dy) > 8) {
                axisLocked = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y'
            }
        }

        if (axisLocked === 'x') {
            if (e.cancelable) {
                e.preventDefault()
            }

            currentX = dx
            trackStyle.value = {
                transform: `translateX(calc(-33.3333% + ${dx}px))`,
            }
        }
    }

    function onTouchEnd(): void {
        if (!dragging) {
            return
        }

        dragging = false

        if (axisLocked !== 'x') {
            trackStyle.value = {}

            return
        }

        const width = viewportEl?.offsetWidth ?? 320
        const limit = width * threshold

        isAnimating.value = true

        if (currentX < -limit) {
            // 次へ
            trackStyle.value = { transform: 'translateX(-66.6666%)' }
            window.setTimeout(() => {
                isAnimating.value = false
                trackStyle.value = {}
                options.onNext()
            }, duration)
        } else if (currentX > limit) {
            // 前へ
            trackStyle.value = { transform: 'translateX(0)' }
            window.setTimeout(() => {
                isAnimating.value = false
                trackStyle.value = {}
                options.onPrev()
            }, duration)
        } else {
            // 戻す
            trackStyle.value = {}
            window.setTimeout(() => {
                isAnimating.value = false
            }, duration)
        }
    }

    function onMouseDown(e: MouseEvent): void {
        onTouchStart(e)

        const moveHandler = (ev: MouseEvent) => onTouchMove(ev)
        const upHandler = () => {
            onTouchEnd()
            document.removeEventListener('mousemove', moveHandler)
            document.removeEventListener('mouseup', upHandler)
        }

        document.addEventListener('mousemove', moveHandler)
        document.addEventListener('mouseup', upHandler)
    }

    return {
        trackStyle,
        isAnimating,
        onTouchStart,
        onTouchMove,
        onTouchEnd,
        onMouseDown,
    }
}

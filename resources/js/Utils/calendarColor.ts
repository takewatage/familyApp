import type { CalendarEvent } from '@/Types/calendar'

/**
 * イベントの色を CSS の色に解決する。
 * Vuetify テーマカラー名なら rgb(var(--v-theme-xxx)) を、
 * それ以外（#xxx や rgb）はそのまま返す。未指定は primary。
 */
export function resolveEventColor(ev: CalendarEvent): string {
    const c = ev.color

    if (!c) {
        return 'rgb(var(--v-theme-primary))'
    }

    // テーマカラー名っぽい（英字のみ）ならCSS変数に変換
    if (/^[a-z-]+$/i.test(c)) {
        return `rgb(var(--v-theme-${c}))`
    }

    return c
}

/** 白文字のコントラスト比がこれ未満の明るい背景だけ黒文字にする */
const MIN_WHITE_TEXT_CONTRAST = 2.2

/**
 * 予定チップの文字色。基本は白で、明るい背景（イエロー・ライム・アンバー等）だけ黒にする。
 * #rrggbb 以外（テーマカラー名など）は白とする。
 */
export function eventTextColor(ev: CalendarEvent): string {
    return foregroundOn(ev.color ?? '')
}

/**
 * 背景色の上に置く文字・アイコンの色（白 or 黒）。判定基準は eventTextColor と同じ。
 */
export function foregroundOn(c: string): string {
    const m = /^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i.exec(c)

    if (!m) {
        return '#fff'
    }

    const [r, g, b] = [m[1], m[2], m[3]].map((h) => {
        const v = parseInt(h, 16) / 255

        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4
    })
    const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b

    const whiteContrast = 1.05 / (luminance + 0.05)

    return whiteContrast >= MIN_WHITE_TEXT_CONTRAST ? '#fff' : 'rgba(0, 0, 0, 0.87)'
}

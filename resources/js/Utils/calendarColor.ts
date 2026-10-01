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

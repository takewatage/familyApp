// カレンダーの日付計算に使う純粋関数。副作用なし・テストしやすい単位で分離。
// 表示用の整形（「YYYY年M月D日」等）は dayjs ベースの dateFormatter.ts を使う。

import type { DateKey, YearMonth } from '@/Types/calendar'

/** Date を 'YYYY-MM-DD' に変換 */
export function toDateKey(d: Date): DateKey {
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')

    return `${y}-${m}-${day}`
}

/** 'YYYY-MM-DD' を Date に変換（ローカルタイム） */
export function fromDateKey(key: DateKey): Date {
    const [y, m, d] = key.split('-').map(Number)

    return new Date(y, m - 1, d)
}

/** 今日のキーを返す */
export function todayKey(): DateKey {
    return toDateKey(new Date())
}

/** year/month から delta ヶ月ずらした YearMonth を返す */
export function shiftMonth(
    year: number,
    month: number,
    delta: number,
): YearMonth {
    const d = new Date(year, month + delta, 1)

    return { year: d.getFullYear(), month: d.getMonth() }
}

/** その月の日数 */
export function daysInMonth(year: number, month: number): number {
    return new Date(year, month + 1, 0).getDate()
}

/** その月の1日の曜日（0=日） */
export function firstDayOfWeek(year: number, month: number): number {
    return new Date(year, month, 1).getDay()
}

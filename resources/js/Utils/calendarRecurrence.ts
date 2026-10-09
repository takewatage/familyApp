// 予定の繰り返しルール（RFC 5545 の RRULE）と、編集フォームの入力値の相互変換。
// フォームで扱えない形（INTERVAL が 2 以上など）は「カスタム」として元のルールをそのまま保つ。

import { fromDateKey } from '@/Utils/calendarDate'
import type { DateKey } from '@/Types/calendar'

export type RecurrenceFreq = 'none' | 'DAILY' | 'WEEKLY' | 'MONTHLY' | 'YEARLY' | 'custom'

export interface RecurrenceForm {
    freq: RecurrenceFreq
    /** 毎週: 曜日（0=日〜6=土） */
    weekdays: number[]
    /** 毎月: 日付で繰り返すか、第n曜日で繰り返すか */
    monthlyBy: 'date' | 'weekday'
    /** 終了: なし / 日付 / 回数 */
    endType: 'never' | 'until' | 'count'
    until: DateKey | null
    count: number
    /** カスタムのとき、元のルール */
    raw: string | null
}

const BYDAY = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA']
const WEEKDAY_LABELS = ['日', '月', '火', '水', '木', '金', '土']

export function weekdayLabel(day: number): string {
    return WEEKDAY_LABELS[day]
}

/** その日が月の第何週目の曜日か（5 週目は「最終」として -1） */
export function nthWeekdayOfMonth(date: DateKey): number {
    const d = fromDateKey(date)
    const n = Math.ceil(d.getDate() / 7)

    return n >= 5 ? -1 : n
}

function parseParts(rrule: string): Record<string, string> {
    return Object.fromEntries(
        rrule
            .replace(/^RRULE:/i, '')
            .split(';')
            .filter(Boolean)
            .map((pair) => {
                const [key, value = ''] = pair.split('=')

                return [key.toUpperCase(), value.toUpperCase()]
            }),
    )
}

export function emptyRecurrence(startDate: DateKey): RecurrenceForm {
    return {
        freq: 'none',
        weekdays: [fromDateKey(startDate).getDay()],
        monthlyBy: 'date',
        endType: 'never',
        until: null,
        count: 10,
        raw: null,
    }
}

/** RRULE をフォームの入力値にする */
export function parseRecurrence(rrule: string | null, startDate: DateKey): RecurrenceForm {
    const form = emptyRecurrence(startDate)

    if (!rrule) {
        return form
    }

    const parts = parseParts(rrule)
    const known = ['FREQ', 'BYDAY', 'BYMONTHDAY', 'UNTIL', 'COUNT', 'INTERVAL', 'WKST']
    const freq = parts.FREQ as RecurrenceFreq

    if (
        !['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'].includes(freq) ||
        Object.keys(parts).some((k) => !known.includes(k)) ||
        (parts.INTERVAL && parts.INTERVAL !== '1')
    ) {
        return { ...form, freq: 'custom', raw: rrule }
    }

    // 毎月・毎年は開始日から日付・曜日を決めるため、ルールが開始日と合わない場合はカスタム扱いにする
    // （説明文と実際の発生日が食い違わないように）
    const start = fromDateKey(startDate)
    const byday = parts.BYDAY ?? ''
    const monthlyMatches =
        freq !== 'MONTHLY' ||
        (parts.BYMONTHDAY
            ? Number(parts.BYMONTHDAY) === start.getDate() && !byday
            : byday === `${nthWeekdayOfMonth(startDate)}${BYDAY[start.getDay()]}` || !byday)
    const yearlyMatches = freq !== 'YEARLY' || (!parts.BYMONTHDAY && !byday && !parts.BYMONTH)

    if (!monthlyMatches || !yearlyMatches) {
        return { ...form, freq: 'custom', raw: rrule }
    }

    form.freq = freq

    if (freq === 'WEEKLY' && parts.BYDAY) {
        form.weekdays = parts.BYDAY.split(',')
            .map((d) => BYDAY.indexOf(d))
            .filter((d) => d >= 0)
    }

    if (freq === 'MONTHLY' && parts.BYDAY) {
        form.monthlyBy = 'weekday'
    }

    if (parts.UNTIL) {
        const u = parts.UNTIL.slice(0, 8)

        form.endType = 'until'
        form.until = `${u.slice(0, 4)}-${u.slice(4, 6)}-${u.slice(6, 8)}`
    } else if (parts.COUNT) {
        form.endType = 'count'
        form.count = Number(parts.COUNT)
    }

    return form
}

/** フォームの入力値を RRULE にする（繰り返さないなら null） */
export function buildRecurrence(form: RecurrenceForm, startDate: DateKey): string | null {
    if (form.freq === 'none') {
        return null
    }

    if (form.freq === 'custom') {
        return form.raw
    }

    const parts = [`FREQ=${form.freq}`]
    const start = fromDateKey(startDate)

    if (form.freq === 'WEEKLY') {
        const days = form.weekdays.length ? [...form.weekdays].sort() : [start.getDay()]

        parts.push(`BYDAY=${days.map((d) => BYDAY[d]).join(',')}`)
    }

    if (form.freq === 'MONTHLY') {
        parts.push(
            form.monthlyBy === 'weekday'
                ? `BYDAY=${nthWeekdayOfMonth(startDate)}${BYDAY[start.getDay()]}`
                : `BYMONTHDAY=${start.getDate()}`,
        )
    }

    if (form.endType === 'until' && form.until) {
        parts.push(`UNTIL=${form.until.replaceAll('-', '')}`)
    } else if (form.endType === 'count' && form.count > 0) {
        parts.push(`COUNT=${Math.floor(form.count)}`)
    }

    return parts.join(';')
}

/**
 * 開始日を変えたとき、繰り返しのルールを新しい開始日に合わせる
 * （毎週は元の開始日の曜日を新しい曜日に置き換え、毎月・毎年は新しい開始日から作り直す。カスタムはそのまま）
 */
export function adaptRecurrenceToStart(rrule: string | null, oldStart: DateKey, newStart: DateKey): string | null {
    if (!rrule || oldStart === newStart) {
        return rrule
    }

    const form = parseRecurrence(rrule, oldStart)

    if (form.freq === 'custom' || form.freq === 'none') {
        return rrule
    }

    if (form.freq === 'WEEKLY') {
        const oldDay = fromDateKey(oldStart).getDay()
        const newDay = fromDateKey(newStart).getDay()

        form.weekdays = [...new Set(form.weekdays.map((d) => (d === oldDay ? newDay : d)))]
    }

    // 終了日が新しい開始日より前になったら、開始日に合わせる
    if (form.endType === 'until' && form.until && form.until < newStart) {
        form.until = newStart
    }

    return buildRecurrence(form, newStart)
}

/** 繰り返しの説明文（例: 毎週 月・水 / 毎月 第2火曜日） */
export function describeRecurrence(rrule: string | null, startDate: DateKey): string {
    const form = parseRecurrence(rrule, startDate)
    const start = fromDateKey(startDate)

    const base = {
        none: '繰り返さない',
        custom: 'カスタム',
        DAILY: '毎日',
        WEEKLY: `毎週 ${form.weekdays.map(weekdayLabel).join('・')}曜日`,
        MONTHLY:
            form.monthlyBy === 'weekday'
                ? `毎月 ${nthWeekdayOfMonth(startDate) === -1 ? '最終' : `第${nthWeekdayOfMonth(startDate)}`}${weekdayLabel(start.getDay())}曜日`
                : `毎月 ${start.getDate()}日`,
        YEARLY: `毎年 ${start.getMonth() + 1}月${start.getDate()}日`,
    }[form.freq]

    if (form.endType === 'until' && form.until) {
        return `${base}（${form.until.replaceAll('-', '/')}まで）`
    }

    if (form.endType === 'count') {
        return `${base}（${form.count}回）`
    }

    return base
}

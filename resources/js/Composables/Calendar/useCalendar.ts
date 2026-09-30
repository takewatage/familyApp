// カレンダーの状態（表示年月・選択日）と、月グリッドのセル生成ロジックを担うcomposable。
// 表示の都合に依存しない純粋なロジックをここに集約する。
//
// 家計簿の useMonthNavigation とは役割が異なる（あちらは Inertia 遷移でサーバーから
// 取り直す月切替、こちらはクライアント内で表示月を変える月移動）。

import { computed, ref, type ComputedRef, type Ref } from 'vue'
import type {
    DateKey,
    DayCellData,
    EventMap,
    YearMonth,
} from '@/Types/calendar'
import {
    daysInMonth,
    firstDayOfWeek,
    shiftMonth,
    toDateKey,
    todayKey,
} from '@/Utils/calendarDate'

export interface UseCalendarOptions {
    /** 初期表示年。未指定なら今年。 */
    initialYear?: number
    /** 初期表示月（0始まり）。未指定なら今月。 */
    initialMonth?: number
    /** イベントマップ（リアクティブに変化しうる） */
    events: Ref<EventMap> | ComputedRef<EventMap>
}

export interface UseCalendarReturn {
    viewYear: Ref<number>
    viewMonth: Ref<number>
    selectedKey: Ref<DateKey>
    prevYM: ComputedRef<YearMonth>
    nextYM: ComputedRef<YearMonth>
    /** 指定年月の6週分セルを生成する */
    buildCells: (year: number, month: number) => DayCellData[]
    /** 現在表示月のセル */
    currentCells: ComputedRef<DayCellData[]>
    prevCells: ComputedRef<DayCellData[]>
    nextCells: ComputedRef<DayCellData[]>
    changeMonth: (delta: number) => void
    goToYearMonth: (year: number, month: number) => void
    goToday: () => void
    selectDate: (key: DateKey) => void
    /** 選択中の日付のイベント */
    selectedEvents: ComputedRef<DayCellData['events']>
}

export function useCalendar(options: UseCalendarOptions): UseCalendarReturn {
    const now = new Date()
    const viewYear = ref(options.initialYear ?? now.getFullYear())
    const viewMonth = ref(options.initialMonth ?? now.getMonth())
    const selectedKey = ref<DateKey>(todayKey())

    const events = options.events

    const prevYM = computed(() =>
        shiftMonth(viewYear.value, viewMonth.value, -1),
    )
    const nextYM = computed(() =>
        shiftMonth(viewYear.value, viewMonth.value, 1),
    )

    /**
     * 指定年月の月グリッド（前月埋め + 当月 + 翌月埋め）を生成する。
     * キーは必ず実在する日付の 'YYYY-MM-DD' を入れる（v-forのkey衝突を防ぐため）。
     */
    function buildCells(year: number, month: number): DayCellData[] {
        const startDow = firstDayOfWeek(year, month)
        const total = daysInMonth(year, month)
        const today = todayKey()
        const evMap = events.value
        const list: DayCellData[] = []

        // 前月埋め: 月初の曜日数ぶん、前月末から
        for (let i = 0; i < startDow; i++) {
            const d = new Date(year, month, -(startDow - 1 - i))
            const key = toDateKey(d)

            list.push({
                day: d.getDate(),
                key,
                dow: d.getDay(),
                isToday: key === today,
                isOtherMonth: true,
                events: evMap[key] ?? [],
            })
        }

        // 当月
        for (let day = 1; day <= total; day++) {
            const d = new Date(year, month, day)
            const key = toDateKey(d)

            list.push({
                day,
                key,
                dow: d.getDay(),
                isToday: key === today,
                isOtherMonth: false,
                events: evMap[key] ?? [],
            })
        }

        // 翌月埋め（7の倍数になるまで）
        const trailing = (7 - (list.length % 7)) % 7

        for (let t = 1; t <= trailing; t++) {
            const d = new Date(year, month + 1, t)
            const key = toDateKey(d)

            list.push({
                day: t,
                key,
                dow: d.getDay(),
                isToday: key === today,
                isOtherMonth: true,
                events: evMap[key] ?? [],
            })
        }

        return list
    }

    const currentCells = computed(() =>
        buildCells(viewYear.value, viewMonth.value),
    )
    const prevCells = computed(() =>
        buildCells(prevYM.value.year, prevYM.value.month),
    )
    const nextCells = computed(() =>
        buildCells(nextYM.value.year, nextYM.value.month),
    )

    function changeMonth(delta: number): void {
        const s = shiftMonth(viewYear.value, viewMonth.value, delta)

        viewYear.value = s.year
        viewMonth.value = s.month
    }

    function goToYearMonth(year: number, month: number): void {
        viewYear.value = year
        viewMonth.value = month
    }

    function goToday(): void {
        const t = new Date()

        viewYear.value = t.getFullYear()
        viewMonth.value = t.getMonth()
        selectedKey.value = toDateKey(t)
    }

    function selectDate(key: DateKey): void {
        selectedKey.value = key
    }

    const selectedEvents = computed(() => events.value[selectedKey.value] ?? [])

    return {
        viewYear,
        viewMonth,
        selectedKey,
        prevYM,
        nextYM,
        buildCells,
        currentCells,
        prevCells,
        nextCells,
        changeMonth,
        goToYearMonth,
        goToday,
        selectDate,
        selectedEvents,
    }
}

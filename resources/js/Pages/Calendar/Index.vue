<script setup lang="ts">
// カレンダー画面（モックアップ）。
// 機能が未確定のため、サーバーからはデータを受け取らず、ダミーの予定をこのページで保持する。
// 予定の追加はページ内のメモリにだけ反映し、リロードすると消える。

import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SwipeCalendar from '@/Components/Calendar/SwipeCalendar.vue'
import EventEditForm from '@/Components/Calendar/EventEditForm.vue'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { fromDateKey, toDateKey, todayKey } from '@/Utils/calendarDate'
import { resolveEventColor } from '@/Utils/calendarColor'
import type { CalendarEvent, DateKey, EventEditModel, EventMap } from '@/Types/calendar'

defineOptions({ layout: AuthenticatedLayout })

/** 予定の種類（将来のデータ連携元を想定したモック上の分類） */
type EventSource = 'family' | 'payment' | 'birthday'

const SOURCE_STYLES: Record<EventSource, { icon: string; color: string }> = {
    family: { icon: 'mdi-account-group', color: 'primary' },
    payment: { icon: 'mdi-cash-clock', color: 'error' },
    birthday: { icon: 'mdi-cake-variant', color: 'warning' },
}

/** 今日を基準に n 日ずらした日付キーを返す */
function keyFromToday(offsetDays: number): DateKey {
    const d = new Date()

    d.setDate(d.getDate() + offsetDays)

    return toDateKey(d)
}

function mockEvent(id: number, source: EventSource, title: string, time?: string): CalendarEvent {
    return { id, title, time, ...SOURCE_STYLES[source], meta: { source } }
}

// 表示確認用のダミー予定
const events = ref<EventMap>({
    [keyFromToday(0)]: [
        mockEvent(1, 'family', '買い出し', '10:00'),
        mockEvent(2, 'family', '習い事の送迎', '16:30'),
    ],
    [keyFromToday(2)]: [mockEvent(3, 'birthday', 'パパの誕生日')],
    [keyFromToday(4)]: [
        mockEvent(4, 'payment', '家賃'),
        mockEvent(5, 'payment', '電気代'),
        mockEvent(6, 'family', '保育園の面談', '18:00'),
    ],
    [keyFromToday(6)]: [mockEvent(7, 'family', '家族で外食', '19:00')],
    [keyFromToday(12)]: [mockEvent(8, 'payment', '動画サブスク')],
    [keyFromToday(-3)]: [mockEvent(9, 'family', '大掃除')],
})

let nextId = 100

// 今日から7日間の予定（一覧表示用）
const upcomingEvents = computed(() => {
    const days = Array.from({ length: 7 }, (_, i) => keyFromToday(i))

    return days
        .filter((key) => events.value[key]?.length)
        .map((key) => ({ key, label: formatDayLabel(key), events: events.value[key] }))
})

function formatDayLabel(key: DateKey): string {
    const d = fromDateKey(key)
    const weekdays = ['日', '月', '火', '水', '木', '金', '土']

    return `${d.getMonth() + 1}/${d.getDate()}（${weekdays[d.getDay()]}）`
}

// 予定追加の対象日（最後にタップした日。未選択なら今日）
const selectedDate = ref<DateKey>(todayKey())

function onSelectDate(key: DateKey): void {
    selectedDate.value = key
}

const dialogService = useDialogService()

async function openAddDialog(): Promise<void> {
    const dialog = dialogService.open<EventEditModel>({
        component: EventEditForm,
        props: {
            initial: { startDate: selectedDate.value, endDate: selectedDate.value },
        },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: { title: '予定を追加' },
    })

    const result = await dialog.afterClosed()

    if (result) {
        putEvent(result)
    }
}

// 日付詳細シートで予定をタップしたら、同じフォームを編集モードで開く
async function onEventClick(payload: { date: DateKey; event: CalendarEvent }): Promise<void> {
    const dialog = dialogService.open<EventEditModel>({
        component: EventEditForm,
        props: {
            initial: toEditModel(payload.event, payload.date),
        },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: { title: '予定を編集' },
    })

    const result = await dialog.afterClosed()

    if (result) {
        putEvent(result, payload.event)
    }
}

/**
 * 予定をフォームの入力値に変換する。
 * フォームで追加した予定は meta に入力値を持っているのでそれを使い、
 * ダミー予定はタップした日の1日予定として time（'10:00' / '10:00 - 11:00'）から復元する。
 */
function toEditModel(event: CalendarEvent, date: DateKey): EventEditModel {
    const saved = event.meta as Partial<EventEditModel> | undefined

    if (saved?.startDate && saved.endDate) {
        return {
            title: event.title,
            allDay: saved.allDay ?? !event.time,
            startDate: saved.startDate,
            endDate: saved.endDate,
            startTime: saved.startTime ?? '',
            endTime: saved.endTime ?? '',
            color: event.color ?? 'primary',
        }
    }

    const [startTime = '', endTime = ''] = (event.time ?? '').split(' - ')

    return {
        title: event.title,
        allDay: !event.time,
        startDate: date,
        endDate: date,
        startTime,
        endTime,
        color: event.color ?? 'primary',
    }
}

/** 開始日〜終了日の日付キーを列挙する */
function dateKeysInRange(start: DateKey, end: DateKey): DateKey[] {
    const keys: DateKey[] = []
    const d = fromDateKey(start)
    const last = fromDateKey(end)

    while (d <= last) {
        keys.push(toDateKey(d))
        d.setDate(d.getDate() + 1)
    }

    return keys
}

function formatTimeLabel(form: EventEditModel): string | undefined {
    if (form.allDay) {
        return undefined
    }

    return form.endTime ? `${form.startTime} - ${form.endTime}` : form.startTime
}

// 予定はページ内のメモリにだけ保持する（モックのため保存しない）
// original を渡すと編集: 同じ id の予定を全日付から外し、種類・アイコンを引き継いで入れ直す
// 複数日にまたがる予定は、期間中の各日に同じ予定を表示する
function putEvent(form: EventEditModel, original?: CalendarEvent): void {
    const source = (original?.meta?.source as EventSource | undefined) ?? 'family'
    const event: CalendarEvent = {
        id: original?.id ?? nextId++,
        title: form.title,
        time: formatTimeLabel(form),
        color: form.color,
        icon: original?.icon ?? SOURCE_STYLES[source].icon,
        meta: { source, ...form },
    }

    const next: EventMap = {}

    for (const [key, list] of Object.entries(events.value)) {
        const rest = original ? list.filter((e) => e.id !== original.id) : list

        if (rest.length) {
            next[key] = rest
        }
    }

    for (const key of dateKeysInRange(form.startDate, form.endDate)) {
        next[key] = [...(next[key] ?? []), event]
    }

    events.value = next
}
</script>

<template>
    <Head title="カレンダー" />

    <v-container class="pa-0">
        <SwipeCalendar
            :events="events"
            :max-events-per-cell="3"
            @select-date="onSelectDate"
            @event-click="onEventClick" />

        <div class="d-flex justify-end pr-4 pt-2">
            <v-btn
                icon="mdi-plus"
                color="primary"
                aria-label="予定を追加"
                @click="openAddDialog" />
        </div>

        <section class="pa-2">
            <p class="text-subtitle-2 font-weight-bold mb-2">これから7日間の予定</p>

            <v-card variant="outlined">
                <v-list
                    v-if="upcomingEvents.length"
                    density="compact">
                    <template
                        v-for="day in upcomingEvents"
                        :key="day.key">
                        <v-list-subheader>{{ day.label }}</v-list-subheader>
                        <v-list-item
                            v-for="event in day.events"
                            :key="event.id"
                            :title="event.title"
                            :subtitle="event.time ?? '終日'"
                            class="upcoming-event"
                            :style="{ borderLeftColor: resolveEventColor(event) }" />
                    </template>
                </v-list>
                <v-card-text
                    v-else
                    class="text-center text-medium-emphasis py-6">
                    これから7日間の予定はありません
                </v-card-text>
            </v-card>
        </section>
    </v-container>
</template>

<style scoped>
/* 予定の色を左の縦線で示す */
.upcoming-event {
    border-left: 4px solid;
    margin: 2px 8px;
    border-radius: 4px;
}
</style>

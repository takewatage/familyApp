<script setup lang="ts">
// SwipeCalendar の動作確認用ページ。
// メニューには載せず URL 直打ち（/calendar/demo）で開く。
// サーバーからはデータを受け取らず、ダミーイベントをこのページで保持する。

import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SwipeCalendar from '@/Components/Calendar/SwipeCalendar.vue'
import { toDateKey } from '@/Utils/calendarDate'
import type {
    DateKey,
    EventFormModel,
    EventMap,
    YearMonth,
} from '@/Types/calendar'

defineOptions({ layout: AuthenticatedLayout })

const enableAdd = ref(false)
const logs = ref<string[]>([])

/** 今日を基準に n 日ずらした日付キーを返す */
function keyFromToday(offsetDays: number): DateKey {
    const d = new Date()

    d.setDate(d.getDate() + offsetDays)

    return toDateKey(d)
}

// 表示確認用のダミーイベント（テーマカラー名と CSS カラーの両方を含む）
const events = computed<EventMap>(() => ({
    [keyFromToday(0)]: [
        {
            id: 1,
            title: 'スーパー',
            color: 'primary',
            time: '10:00',
            icon: 'mdi-cart',
        },
        { id: 2, title: 'ドラッグストア', color: 'success', time: '15:30' },
    ],
    [keyFromToday(1)]: [{ id: 3, title: '保育園の遠足', color: 'warning' }],
    [keyFromToday(3)]: [
        { id: 4, title: '家賃引き落とし', color: 'error' },
        { id: 5, title: 'electricity', color: '#8e24aa' },
        { id: 6, title: '水道代', color: 'info' },
        { id: 7, title: 'サブスク', color: 'primary' },
    ],
    [keyFromToday(-2)]: [
        {
            id: 8,
            title: 'とても長いタイトルのイベントで省略表示を確認する',
            color: 'primary',
        },
    ],
    [keyFromToday(10)]: [
        { id: 9, title: '給料日', color: 'success', time: '終日' },
    ],
}))

function addLog(message: string): void {
    logs.value.unshift(`${new Date().toLocaleTimeString()}  ${message}`)

    if (logs.value.length > 20) {
        logs.value.pop()
    }
}

function onSelectDate(key: DateKey): void {
    addLog(`select-date: ${key}`)
}

function onMonthChange(value: YearMonth): void {
    addLog(`month-change: year=${value.year}, month=${value.month}（0始まり）`)
}

function onAddEvent(payload: { date: DateKey; event: EventFormModel }): void {
    addLog(
        `add-event: date=${payload.date}, title=${payload.event.title}, time=${payload.event.time || '(なし)'}, color=${payload.event.color}`,
    )
}

function clearLogs(): void {
    logs.value = []
}
</script>

<template>
    <Head title="カレンダー動作確認" />

    <v-container class="pa-2">
        <v-alert
            type="info"
            variant="tonal"
            density="compact"
            class="mb-3">
            SwipeCalendar
            の動作確認ページです。イベントはこのページ内のダミーデータで、保存はされません。
        </v-alert>

        <v-card
            variant="outlined"
            class="mb-3">
            <v-card-text class="py-2">
                <v-switch
                    v-model="enableAdd"
                    color="primary"
                    density="compact"
                    hide-details
                    label="予定追加機能を有効にする（enableAdd）" />
            </v-card-text>
        </v-card>

        <v-card variant="outlined">
            <SwipeCalendar
                :events="events"
                :enable-add="enableAdd"
                @select-date="onSelectDate"
                @month-change="onMonthChange"
                @add-event="onAddEvent" />
        </v-card>

        <v-card
            variant="outlined"
            class="mt-3">
            <v-toolbar
                density="compact"
                flat
                color="transparent">
                <v-toolbar-title class="text-subtitle-2">
                    emit ログ
                </v-toolbar-title>
                <template #append>
                    <v-btn
                        size="small"
                        variant="text"
                        @click="clearLogs">
                        クリア
                    </v-btn>
                </template>
            </v-toolbar>
            <v-divider />
            <v-list
                v-if="logs.length"
                density="compact">
                <v-list-item
                    v-for="(log, i) in logs"
                    :key="i"
                    :title="log"
                    class="text-caption" />
            </v-list>
            <v-card-text
                v-else
                class="text-center text-medium-emphasis py-6">
                カレンダーを操作するとここに emit が表示されます
            </v-card-text>
        </v-card>
    </v-container>
</template>

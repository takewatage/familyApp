<script setup lang="ts">
// カレンダー画面。予定は家族で共有し、表示中の月（前後の月を含む）を API から取得する。
// 予定の追加・編集・削除は EventEditForm（全画面）で行い、保存後に表示中の期間を取り直す。
// 繰り返し予定の変更・削除は、範囲（この予定のみ / これ以降 / すべて）を選んでから送る。

import { computed, onMounted, ref } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import SwipeCalendar from '@/Components/Calendar/SwipeCalendar.vue'
import EventEditForm from '@/Components/Calendar/EventEditForm.vue'
import ParticipantAvatars from '@/Components/Calendar/ParticipantAvatars.vue'
import RecurrenceScopeDialog from '@/Components/Calendar/RecurrenceScopeDialog.vue'
import CalendarSettingsSheet from '@/Components/Calendar/CalendarSettingsSheet.vue'
import LabelEditForm from '@/Components/Calendar/LabelEditForm.vue'
import { calendarApi, type RecurrenceScope } from '@/Api/calendarApi'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { fromDateKey, toDateKey, todayKey } from '@/Utils/calendarDate'
import { resolveEventColor } from '@/Utils/calendarColor'
import { BIRTHDAY_COLOR, DEFAULT_CALENDAR_COLOR } from '@/Constants/calendarColors'
import type {
    CalendarEvent,
    CalendarLabel,
    CalendarParticipant,
    DateKey,
    EventEditModel,
    EventEditResult,
    EventMap,
    YearMonth,
} from '@/Types/calendar'
import type {
    CalendarEventRequest,
    CalendarEventResult,
    CalendarPageResult,
    CalendarSettingsResult,
} from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<CalendarPageResult>()

/** 参加者の選択肢（家族メンバー） */
const participants = computed<CalendarParticipant[]>(() =>
    props.participants.map((p) => ({ id: p.id, name: p.name, avatarUrl: p.avatarUrl ?? null })),
)

/** 新しい予定の参加者の初期値（ログイン中のユーザー自身。家族のメンバーにいる場合のみ） */
const page = usePage()
const defaultParticipantIds = computed<string[]>(() => {
    const userId = (page.props.auth as { user?: { id: string } } | undefined)?.user?.id

    return userId && participants.value.some((p) => p.id === userId) ? [userId] : []
})

/** ラベル（家族で共有。編集すると API で保存する） */
const labels = ref<CalendarLabel[]>(props.labels.map((l) => ({ ...l })))

const dialogService = useDialogService()
const { confirm } = useConfirmDialog()

// ---------------------------------------------------------------- カレンダー設定

/** 家族のカレンダー設定（誕生日のラベル等） */
const settings = ref<CalendarSettingsResult>({ ...(props.settings ?? {}) })
const settingsOpen = ref(false)

/** 設定からラベルの編集画面を開く（予定フォームからと同じ画面・同じ保存処理） */
async function openLabelEditorFromSettings(): Promise<void> {
    const dialog = dialogService.open<CalendarLabel[]>({
        component: LabelEditForm,
        props: { labels: labels.value },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: { title: 'ラベルの編集' },
    })

    const result = await dialog.afterClosed()

    if (result) {
        await onLabelsChange(result)
    }
}

async function onBirthdayLabelChange(labelId: string | null): Promise<void> {
    try {
        const res = await calendarApi.updateSettings({ birthdayLabelId: labelId })

        settings.value = res.data.settings
    } catch {
        // エラーは client が表示する
        return
    }

    await fetchEvents()
}

// ---------------------------------------------------------------- 予定の取得

/** 表示中の月（0 始まり） */
const viewMonth = ref<YearMonth>({ year: new Date().getFullYear(), month: new Date().getMonth() })

const monthEvents = ref<CalendarEventResult[]>([])
const upcomingResults = ref<CalendarEventResult[]>([])
const loading = ref(false)

// 月移動が続いたとき、古い月の結果で上書きしないよう最新のリクエストだけを反映する
let requestSeq = 0

/** 表示中の月の前月 1 日〜翌月末（スワイプ中に前後の月も見えるため） */
function monthRange(ym: YearMonth): { from: DateKey; to: DateKey } {
    return {
        from: toDateKey(new Date(ym.year, ym.month - 1, 1)),
        to: toDateKey(new Date(ym.year, ym.month + 2, 0)),
    }
}

function keyFromToday(offsetDays: number): DateKey {
    const d = new Date()

    d.setDate(d.getDate() + offsetDays)

    return toDateKey(d)
}

async function fetchMonth(): Promise<void> {
    const seq = ++requestSeq
    const { from, to } = monthRange(viewMonth.value)

    loading.value = true

    try {
        const res = await calendarApi.events(from, to)

        if (seq === requestSeq) {
            monthEvents.value = res.data.events
        }
    } finally {
        if (seq === requestSeq) {
            loading.value = false
        }
    }
}

/** 「これから7日間」は表示中の月に関係しないため、月移動では取り直さない */
async function fetchUpcoming(): Promise<void> {
    const res = await calendarApi.events(todayKey(), keyFromToday(6))

    upcomingResults.value = res.data.events
}

/** 保存・削除の後: 表示中の月と「これから7日間」の両方を取り直す */
async function fetchEvents(): Promise<void> {
    await Promise.all([fetchMonth(), fetchUpcoming()])
}

function onMonthChange(ym: YearMonth): void {
    viewMonth.value = ym
    fetchMonth()
}

onMounted(fetchEvents)

// ---------------------------------------------------------------- 表示用の変換

const labelColor = computed(() => new Map(labels.value.map((l) => [l.id, l.color])))

function formatTime(r: CalendarEventResult): string | undefined {
    if (r.allDay || !r.startTime) {
        return undefined
    }

    return r.endTime ? `${r.startTime} - ${r.endTime}` : r.startTime
}

function toCalendarEvent(r: CalendarEventResult): CalendarEvent {
    return {
        // 繰り返しは同じ予定 ID の回が並ぶため、発生日と組み合わせて一意にする
        id: `${r.id}:${r.occurrenceDate}`,
        title: r.title,
        time: formatTime(r),
        // 誕生日はカレンダー設定の「誕生日のラベル」の色（未設定なら既定の色）
        color: labelColor.value.get(r.labelId ?? '') ?? (r.isBirthday ? BIRTHDAY_COLOR : DEFAULT_CALENDAR_COLOR),
        icon: r.isBirthday ? 'mdi-cake-variant' : r.isRecurring ? 'mdi-repeat' : undefined,
        participants: participants.value.filter((p) => r.participantIds.includes(p.id)),
        meta: { result: r },
    }
}

/** 予定を日付ごとにまとめる（複数日の予定は期間中の各日に表示する） */
function toEventMap(results: CalendarEventResult[]): EventMap {
    const map: EventMap = {}

    for (const r of results) {
        const event = toCalendarEvent(r)
        const d = fromDateKey(r.startDate)
        const last = fromDateKey(r.endDate)

        while (d <= last) {
            const key = toDateKey(d)

            map[key] = [...(map[key] ?? []), event]
            d.setDate(d.getDate() + 1)
        }
    }

    return map
}

const events = computed<EventMap>(() => toEventMap(monthEvents.value))

// 今日から7日間の予定（一覧表示用）
const upcomingEvents = computed(() => {
    const map = toEventMap(upcomingResults.value)

    return Array.from({ length: 7 }, (_, i) => keyFromToday(i))
        .filter((key) => map[key]?.length)
        .map((key) => ({ key, label: formatDayLabel(key), events: map[key] }))
})

function formatDayLabel(key: DateKey): string {
    const d = fromDateKey(key)
    const weekdays = ['日', '月', '火', '水', '木', '金', '土']

    return `${d.getMonth() + 1}/${d.getDate()}（${weekdays[d.getDay()]}）`
}

// ---------------------------------------------------------------- 追加・編集・削除

// 予定追加の対象日（最後にタップした日。未選択なら今日）
const selectedDate = ref<DateKey>(todayKey())

function onSelectDate(key: DateKey): void {
    selectedDate.value = key
}

// 日別の予定一覧の＋ボタン: その日を開始日にして追加画面を開く
function onAddClick(payload: { date: DateKey }): void {
    selectedDate.value = payload.date
    openAddDialog()
}

/** ラベルの編集を保存し、保存後の一覧を返す（失敗時は client がエラーを表示し、undefined を返す） */
async function onLabelsChange(next: CalendarLabel[]): Promise<CalendarLabel[] | undefined> {
    try {
        const res = await calendarApi.updateLabels(next.map((l) => ({ id: l.id, name: l.name, color: l.color })))

        labels.value = res.data.labels

        return labels.value
    } catch {
        return undefined
    }
}

function openForm(title: string, initial: Partial<EventEditModel>, deletable: boolean): Promise<EventEditResult | undefined> {
    const dialog = dialogService.open<EventEditResult>({
        component: EventEditForm,
        props: {
            initial,
            participants: participants.value,
            labels: labels.value,
            onLabelsChange,
            deletable,
        },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: { title },
    })

    return dialog.afterClosed()
}

function toRequest(form: EventEditModel): CalendarEventRequest {
    return {
        title: form.title,
        memo: form.memo || undefined,
        allDay: form.allDay,
        startDate: form.startDate,
        endDate: form.endDate,
        startTime: form.allDay ? undefined : form.startTime || undefined,
        endTime: form.allDay ? undefined : form.endTime || undefined,
        labelId: form.labelId || undefined,
        participantIds: form.participantIds,
        rrule: form.rrule ?? undefined,
    }
}

async function askScope(mode: 'save' | 'delete'): Promise<RecurrenceScope | undefined> {
    const dialog = dialogService.open<RecurrenceScope>({
        component: RecurrenceScopeDialog,
        props: { mode },
        maxWidth: '400px',
    })

    return dialog.afterClosed()
}

/**
 * フォームを開いて保存する。保存に失敗したら（client がエラーを表示）入力した内容のままフォームを開き直す
 */
async function editUntilSaved(
    title: string,
    initial: Partial<EventEditModel>,
    deletable: boolean,
    save: (form: EventEditModel) => Promise<boolean>,
    remove?: () => Promise<void>,
): Promise<void> {
    let values = initial

    for (;;) {
        const result = await openForm(title, values, deletable)

        if (!result) {
            return
        }

        if (result.type === 'delete') {
            await remove?.()

            return
        }

        try {
            if (await save(result.value)) {
                await fetchEvents()
            }

            return
        } catch {
            values = result.value
        }
    }
}

async function openAddDialog(): Promise<void> {
    const initial = {
        startDate: selectedDate.value,
        endDate: selectedDate.value,
        participantIds: defaultParticipantIds.value,
    }

    await editUntilSaved('予定を追加', initial, false, async (form) => {
        await calendarApi.store(toRequest(form))

        return true
    })
}

// 日別の予定一覧で予定をタップしたら、同じフォームを編集モードで開く（誕生日は編集できない）
async function onEventClick(payload: { date: DateKey; event: CalendarEvent }): Promise<void> {
    const r = payload.event.meta?.result as CalendarEventResult | undefined

    if (!r || r.isBirthday) {
        return
    }

    await editUntilSaved(
        '予定を編集',
        toEditModel(r),
        true,
        async (form) => {
            let scope: RecurrenceScope | undefined

            if (r.isRecurring) {
                scope = await askScope('save')

                // 範囲を選ばずに閉じたら保存しない（入力した内容は破棄）
                if (!scope) {
                    return false
                }
            }

            await calendarApi.update(r.id, { ...toRequest(form), scope, occurrenceDate: r.occurrenceDate })

            return true
        },
        () => deleteEvent(r),
    )
}

async function deleteEvent(r: CalendarEventResult): Promise<void> {
    let scope: RecurrenceScope | undefined

    if (r.isRecurring) {
        scope = await askScope('delete')

        if (!scope) {
            return
        }
    } else {
        const ok = await confirm({
            title: '予定を削除しますか？',
            message: r.title,
            confirmText: '削除する',
            confirmColor: 'error',
        })

        if (!ok) {
            return
        }
    }

    try {
        await calendarApi.destroy(r.id, scope, r.occurrenceDate)
    } catch {
        // エラーは client が表示する
        return
    }

    await fetchEvents()
}

function toEditModel(r: CalendarEventResult): EventEditModel {
    return {
        title: r.title,
        allDay: r.allDay,
        startDate: r.startDate,
        endDate: r.endDate,
        startTime: r.startTime ?? '',
        endTime: r.endTime ?? '',
        // ラベルなしの予定はラベルなしのまま（先頭のラベルで埋めない）
        labelId: r.labelId ?? '',
        participantIds: r.participantIds,
        memo: r.memo ?? '',
        rrule: r.rrule ?? null,
    }
}
</script>

<template>
    <Head title="カレンダー" />

    <v-container class="pa-0">
        <v-progress-linear
            :active="loading"
            indeterminate
            color="primary"
            height="2"
            aria-label="予定を読み込み中" />

        <SwipeCalendar
            :events="events"
            :max-events-per-cell="3"
            detail-fullscreen
            detail-add-button
            @select-date="onSelectDate"
            @month-change="onMonthChange"
            @event-click="onEventClick"
            @add-click="onAddClick">
            <template #header-actions>
                <v-btn
                    icon="mdi-cog-outline"
                    size="small"
                    aria-label="カレンダー設定"
                    @click="settingsOpen = true" />
            </template>
        </SwipeCalendar>

        <CalendarSettingsSheet
            v-model:open="settingsOpen"
            :labels="labels"
            :settings="settings"
            @edit-labels="openLabelEditorFromSettings"
            @update-birthday-label="onBirthdayLabelChange" />

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
                            :style="{ borderLeftColor: resolveEventColor(event) }">
                            <template
                                v-if="event.participants?.length"
                                #append>
                                <ParticipantAvatars :participants="event.participants" />
                            </template>
                        </v-list-item>
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

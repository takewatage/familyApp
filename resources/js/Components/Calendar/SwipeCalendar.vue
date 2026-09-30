<script setup lang="ts">
// メインのカレンダーコンポーネント。
// - イベントは props.events で親から受け取る（親がデータ管理）
// - 横スワイプ / 矢印 / 年月ピッカーで月移動
// - 日付タップで詳細シート、予定追加シート
// - 各所をslotでカスタマイズ可能
// - 色はVuetifyのテーマカラー（primary等）に追従
//
// このコンポーネントはデータの取得・保存を一切行わない。
// add-event を受け取った利用側が永続化まで責任を持つ。

import { computed, ref, toRef, watch } from 'vue'
import type { ComputedRef } from 'vue'
import DayDetailSheet from '@/Components/Calendar/DayDetailSheet.vue'
import EventFormSheet from '@/Components/Calendar/EventFormSheet.vue'
import MonthGrid from '@/Components/Calendar/MonthGrid.vue'
import MonthPickerSheet from '@/Components/Calendar/MonthPickerSheet.vue'
import { useCalendar } from '@/Composables/Calendar/useCalendar'
import { useSwipe } from '@/Composables/Calendar/useSwipe'
import { fromDateKey } from '@/Utils/calendarDate'
import type {
    ColorOption,
    DateKey,
    EventFormModel,
    EventMap,
    YearMonth,
} from '@/Types/calendar'

const props = withDefaults(
    defineProps<{
        /** 日付キーごとのイベントマップ（親が管理） */
        events?: EventMap
        /** 初期表示年 */
        initialYear?: number
        /** 初期表示月（0始まり） */
        initialMonth?: number
        /** 曜日ラベル（7個、日始まり） */
        weekdayLabels?: string[]
        /** 月名ラベル（12個） */
        monthNames?: string[]
        /** 1セルあたりの最大イベント表示数 */
        maxEventsPerCell?: number
        /**
         * 予定追加機能を有効にするか。
         * 本アプリには予定を永続化するテーブルがないため既定は false。
         * 有効にする場合、保存処理は add-event を受け取る利用側が実装すること。
         */
        enableAdd?: boolean
        /** 予定追加フォームのカラー選択肢 */
        colorOptions?: ColorOption[]
    }>(),
    {
        events: () => ({}),
        initialYear: undefined,
        initialMonth: undefined,
        weekdayLabels: () => ['日', '月', '火', '水', '木', '金', '土'],
        monthNames: () => [
            '1月',
            '2月',
            '3月',
            '4月',
            '5月',
            '6月',
            '7月',
            '8月',
            '9月',
            '10月',
            '11月',
            '12月',
        ],
        maxEventsPerCell: 2,
        enableAdd: false,
        colorOptions: undefined,
    },
)

const emit = defineEmits<{
    (e: 'select-date', key: DateKey): void
    (e: 'month-change', value: YearMonth): void
    (e: 'add-event', payload: { date: DateKey; event: EventFormModel }): void
}>()

// events を Ref 化して composable に渡す（props.events は常に存在＝デフォルト {}）
const eventsRef = toRef(props, 'events') as ComputedRef<EventMap>

const cal = useCalendar({
    initialYear: props.initialYear,
    initialMonth: props.initialMonth,
    events: eventsRef,
})

const {
    viewYear,
    viewMonth,
    selectedKey,
    currentCells,
    prevCells,
    nextCells,
    changeMonth,
    goToYearMonth,
    goToday,
    selectDate,
    selectedEvents,
} = cal

// 月が変わったら親に通知（データ取得トリガ用）
watch([viewYear, viewMonth], () => {
    emit('month-change', { year: viewYear.value, month: viewMonth.value })
})

// スワイプ
const {
    trackStyle,
    isAnimating,
    onTouchStart,
    onTouchMove,
    onTouchEnd,
    onMouseDown,
} = useSwipe({
    onPrev: () => changeMonth(-1),
    onNext: () => changeMonth(1),
})

// シート開閉状態
const detailOpen = ref(false)
const addOpen = ref(false)
const pickerOpen = ref(false)

const detailTitle = computed(() => {
    const d = fromDateKey(selectedKey.value)

    return `${d.getMonth() + 1}月${d.getDate()}日`
})

function onSelectDate(key: DateKey): void {
    selectDate(key)
    emit('select-date', key)
    detailOpen.value = true
}

function openAdd(): void {
    detailOpen.value = false
    // 詳細シートが閉じるアニメーション後に開く
    window.setTimeout(() => {
        addOpen.value = true
    }, 200)
}

function openAddDirect(): void {
    addOpen.value = true
}

function onSubmitEvent(form: EventFormModel): void {
    emit('add-event', { date: selectedKey.value, event: form })
}

function openPicker(): void {
    pickerOpen.value = true
}

function onPickerSelect(value: { year: number; month: number }): void {
    goToYearMonth(value.year, value.month)
}
</script>

<template>
    <div class="swipe-calendar">
        <!-- ヘッダー: slotで丸ごと差し替え可能 -->
        <!-- レイアウト側の v-app-bar と競合しないよう v-toolbar を使う -->
        <slot
            name="header"
            :year="viewYear"
            :month="viewMonth"
            :open-add="openAddDirect"
            :go-today="goToday">
            <v-toolbar
                color="primary"
                density="compact"
                flat>
                <v-toolbar-title>{{ viewYear }}年</v-toolbar-title>
                <template #append>
                    <v-btn
                        icon="mdi-calendar-today"
                        size="small"
                        @click="goToday" />
                    <v-btn
                        v-if="enableAdd"
                        icon="mdi-plus"
                        size="small"
                        @click="openAddDirect" />
                </template>
            </v-toolbar>
        </slot>

        <!-- 月ナビゲーション -->
        <slot
            name="nav"
            :month-label="monthNames[viewMonth]"
            :prev="() => changeMonth(-1)"
            :next="() => changeMonth(1)"
            :open-picker="openPicker">
            <v-toolbar
                density="compact"
                flat
                color="transparent">
                <v-btn
                    icon="mdi-chevron-left"
                    variant="text"
                    @click="changeMonth(-1)" />
                <v-spacer />
                <v-btn
                    variant="text"
                    class="text-h6"
                    append-icon="mdi-menu-down"
                    @click="openPicker">
                    {{ monthNames[viewMonth] }}
                </v-btn>
                <v-spacer />
                <v-btn
                    icon="mdi-chevron-right"
                    variant="text"
                    @click="changeMonth(1)" />
            </v-toolbar>
        </slot>

        <!-- 曜日ヘッダー -->
        <div class="swipe-calendar__weekdays">
            <div
                v-for="(label, i) in weekdayLabels"
                :key="i"
                :class="{
                    'swipe-calendar__weekday--sun': i === 0,
                    'swipe-calendar__weekday--sat': i === 6,
                }">
                {{ label }}
            </div>
        </div>

        <!-- スワイプ可能なカレンダー本体 -->
        <div
            class="swipe-calendar__viewport"
            @touchstart.passive="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
            @mousedown="onMouseDown">
            <div
                class="swipe-calendar__track"
                :class="{ 'swipe-calendar__track--animating': isAnimating }"
                :style="trackStyle">
                <div class="swipe-calendar__page">
                    <MonthGrid
                        :cells="prevCells"
                        :selected-key="selectedKey"
                        :max-events="maxEventsPerCell"
                        @select="onSelectDate">
                        <template #day-number="sp">
                            <slot
                                name="day-number"
                                v-bind="sp" />
                        </template>
                        <template #events="sp">
                            <slot
                                name="day-events"
                                v-bind="sp" />
                        </template>
                    </MonthGrid>
                </div>
                <div class="swipe-calendar__page">
                    <MonthGrid
                        :cells="currentCells"
                        :selected-key="selectedKey"
                        :max-events="maxEventsPerCell"
                        @select="onSelectDate">
                        <template #day-number="sp">
                            <slot
                                name="day-number"
                                v-bind="sp" />
                        </template>
                        <template #events="sp">
                            <slot
                                name="day-events"
                                v-bind="sp" />
                        </template>
                    </MonthGrid>
                </div>
                <div class="swipe-calendar__page">
                    <MonthGrid
                        :cells="nextCells"
                        :selected-key="selectedKey"
                        :max-events="maxEventsPerCell"
                        @select="onSelectDate">
                        <template #day-number="sp">
                            <slot
                                name="day-number"
                                v-bind="sp" />
                        </template>
                        <template #events="sp">
                            <slot
                                name="day-events"
                                v-bind="sp" />
                        </template>
                    </MonthGrid>
                </div>
            </div>
        </div>

        <!-- 追加コンテンツ用slot（フッターなど） -->
        <slot
            name="footer"
            :year="viewYear"
            :month="viewMonth" />

        <!-- 日付詳細シート -->
        <DayDetailSheet
            v-model="detailOpen"
            :title="detailTitle"
            :events="selectedEvents"
            :enable-add="enableAdd"
            @add="openAdd">
            <template #event-list="sp">
                <slot
                    name="event-list"
                    v-bind="sp" />
            </template>
        </DayDetailSheet>

        <!-- 予定追加シート -->
        <EventFormSheet
            v-if="enableAdd"
            v-model:open="addOpen"
            :date-label="detailTitle"
            :color-options="colorOptions"
            @submit="onSubmitEvent">
            <template #form-fields="sp">
                <slot
                    name="add-form-fields"
                    v-bind="sp" />
            </template>
        </EventFormSheet>

        <!-- 年月ピッカー -->
        <MonthPickerSheet
            v-model:open="pickerOpen"
            :view-year="viewYear"
            :view-month="viewMonth"
            :month-names="monthNames"
            @select="onPickerSelect"
            @today="goToday" />
    </div>
</template>

<style scoped>
.swipe-calendar {
    display: flex;
    flex-direction: column;
}

.swipe-calendar__weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    padding: 0 4px;
}

.swipe-calendar__weekdays > div {
    text-align: center;
    padding: 8px 0;
    font-size: 11px;
    font-weight: 500;
    color: rgba(var(--v-theme-on-surface), 0.6);
}

.swipe-calendar__weekday--sun {
    color: rgb(var(--v-theme-error)) !important;
}

.swipe-calendar__weekday--sat {
    color: rgb(var(--v-theme-info)) !important;
}

.swipe-calendar__viewport {
    overflow: hidden;
    position: relative;
    touch-action: pan-y;
    user-select: none;
}

.swipe-calendar__track {
    display: flex;
    width: 300%;
    transform: translateX(-33.3333%);
}

.swipe-calendar__track--animating {
    transition: transform 0.28s cubic-bezier(0.25, 0.1, 0.25, 1);
}

.swipe-calendar__page {
    width: 33.3333%;
    flex-shrink: 0;
    padding: 4px;
}
</style>

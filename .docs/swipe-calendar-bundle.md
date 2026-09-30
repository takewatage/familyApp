# SwipeCalendar - 配布用バンドル

このMarkdownには、Vue 3 + Vuetify 3 用のスワイプ対応カレンダーコンポーネント一式が含まれています。
各コードブロックの直前にファイルパスが書いてあるので、その通りにプロジェクトに配置してください。

## 配置先（プロジェクト内の任意の場所、例: `src/components/calendar/`）

```
calendar/
├── types.ts
├── dateUtils.ts
├── useCalendar.ts
├── useSwipe.ts
├── SwipeCalendar.vue
└── components/
    ├── MonthGrid.vue
    ├── DayCell.vue
    ├── DayDetailSheet.vue
    ├── EventFormSheet.vue
    └── MonthPickerSheet.vue
```

`App.vue` は使用例なので、参考にして既存のページから `SwipeCalendar` を呼び出してください。

## 依存

- Vue 3.4+
- Vuetify 3.5+
- `@mdi/font`（アイコン）

---


## `calendar/types.ts`

```typescript
// types.ts
// カレンダーコンポーネント全体で使う型定義

/**
 * 日付キー。'YYYY-MM-DD' 形式の文字列。
 * イベントを日付ごとに引くためのキーとして使う。
 */
export type DateKey = string

/**
 * カレンダーに表示する1件のイベント。
 * 親プロジェクト側のイベント型をそのまま使いたい場合は、
 * この型を拡張するか、変換関数で CalendarEvent[] に変換して渡す。
 */
export interface CalendarEvent {
  /** 一意なID（v-forのkeyや更新・削除に使用） */
  id: string | number
  /** 表示タイトル */
  title: string
  /**
   * イベントの色。
   * Vuetifyのテーマカラー名（'primary' | 'success' など）か、
   * 任意のCSSカラー文字列（'#ff0000' 等）を許容する。
   */
  color?: string
  /** 表示用の時刻文字列（例: '10:00 - 11:30'）。未指定なら終日扱い。 */
  time?: string
  /** リストやアバターに表示するアイコン名（mdi-xxx） */
  icon?: string
  /** 任意の追加データ。親が自由に使ってよい。 */
  meta?: Record<string, unknown>
}

/**
 * 日付キーごとにイベント配列を引けるマップ。
 * 親が `{ '2026-05-01': [ev1, ev2], ... }` の形で渡す。
 */
export type EventMap = Record<DateKey, CalendarEvent[]>

/**
 * 月グリッドの1セル分の情報。
 * useCalendar が生成し、MonthGrid / DayCell が描画に使う。
 */
export interface DayCellData {
  /** 日（1〜31） */
  day: number
  /** 'YYYY-MM-DD' */
  key: DateKey
  /** 曜日（0=日, 6=土） */
  dow: number
  /** 今日かどうか */
  isToday: boolean
  /** 当月外（前月・翌月の埋めセル）かどうか */
  isOtherMonth: boolean
  /** この日のイベント一覧 */
  events: CalendarEvent[]
}

/**
 * 年と月（0始まり）のペア。
 */
export interface YearMonth {
  year: number
  /** 0=1月, 11=12月 */
  month: number
}

/**
 * カレンダーが発火するイベント（emit）の型。
 */
export interface CalendarEmits {
  /** 表示中の年月が変わったとき */
  (e: 'update:viewDate', value: YearMonth): void
  /** 日付が選択されたとき */
  (e: 'select-date', key: DateKey): void
  /** 月が変わったとき（データ取得トリガなどに使う） */
  (e: 'month-change', value: YearMonth): void
  /** 新しい予定が保存されたとき */
  (e: 'add-event', payload: { date: DateKey; event: CalendarEvent }): void
}

/**
 * 予定追加フォームの入力値。
 */
export interface EventFormModel {
  title: string
  time: string
  color: string
}

/**
 * カラー選択肢（予定追加フォームで使用）。
 */
export interface ColorOption {
  title: string
  value: string
}
```

---

## `calendar/dateUtils.ts`

```typescript
// dateUtils.ts
// 日付まわりの純粋関数。副作用なし・テストしやすい単位で分離。

import type { DateKey, YearMonth } from './types'

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
export function shiftMonth(year: number, month: number, delta: number): YearMonth {
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
```

---

## `calendar/useCalendar.ts`

```typescript
// useCalendar.ts
// カレンダーの状態（表示年月・選択日）と、月グリッドのセル生成ロジックを担うcomposable。
// 表示の都合に依存しない純粋なロジックをここに集約する。

import { ref, computed, type Ref, type ComputedRef } from 'vue'
import type { DateKey, DayCellData, EventMap, YearMonth } from './types'
import {
  toDateKey,
  todayKey,
  shiftMonth,
  daysInMonth,
  firstDayOfWeek,
} from './dateUtils'

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

  const prevYM = computed(() => shiftMonth(viewYear.value, viewMonth.value, -1))
  const nextYM = computed(() => shiftMonth(viewYear.value, viewMonth.value, 1))

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

  const currentCells = computed(() => buildCells(viewYear.value, viewMonth.value))
  const prevCells = computed(() => buildCells(prevYM.value.year, prevYM.value.month))
  const nextCells = computed(() => buildCells(nextYM.value.year, nextYM.value.month))

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
```

---

## `calendar/useSwipe.ts`

```typescript
// useSwipe.ts
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
    if (!dragging) return
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
      if (e.cancelable) e.preventDefault()
      currentX = dx
      trackStyle.value = { transform: `translateX(calc(-33.3333% + ${dx}px))` }
    }
  }

  function onTouchEnd(): void {
    if (!dragging) return
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
```

---

## `calendar/SwipeCalendar.vue`

```vue
<script setup lang="ts">
// SwipeCalendar.vue
// メインのカレンダーコンポーネント。
// - イベントは props.events で親から受け取る（親がデータ管理）
// - 横スワイプ / 矢印 / 年月ピッカーで月移動
// - 日付タップで詳細シート、予定追加シート
// - 各所をslotでカスタマイズ可能
// - 色はVuetifyのテーマカラー（primary等）に追従

import { computed, ref, toRef, watch } from 'vue'
import type { ComputedRef } from 'vue'
import type {
  CalendarEvent,
  ColorOption,
  DateKey,
  EventFormModel,
  EventMap,
  YearMonth,
} from './types'
import { useCalendar } from './useCalendar'
import { useSwipe } from './useSwipe'
import { fromDateKey } from './dateUtils'
import MonthGrid from './components/MonthGrid.vue'
import DayDetailSheet from './components/DayDetailSheet.vue'
import EventFormSheet from './components/EventFormSheet.vue'
import MonthPickerSheet from './components/MonthPickerSheet.vue'

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
    /** 予定追加機能を有効にするか */
    enableAdd?: boolean
    /** 予定追加フォームのカラー選択肢 */
    colorOptions?: ColorOption[]
  }>(),
  {
    events: () => ({}),
    weekdayLabels: () => ['日', '月', '火', '水', '木', '金', '土'],
    monthNames: () => [
      '1月', '2月', '3月', '4月', '5月', '6月',
      '7月', '8月', '9月', '10月', '11月', '12月',
    ],
    maxEventsPerCell: 2,
    enableAdd: true,
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
const { trackStyle, isAnimating, onTouchStart, onTouchMove, onTouchEnd, onMouseDown } =
  useSwipe({
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
    <slot name="header" :year="viewYear" :month="viewMonth" :open-add="openAddDirect" :go-today="goToday">
      <v-app-bar color="primary" density="compact" flat>
        <v-app-bar-title>{{ viewYear }}年</v-app-bar-title>
        <template #append>
          <v-btn icon="mdi-calendar-today" size="small" @click="goToday" />
          <v-btn v-if="enableAdd" icon="mdi-plus" size="small" @click="openAddDirect" />
        </template>
      </v-app-bar>
    </slot>

    <!-- 月ナビゲーション -->
    <slot name="nav" :month-label="monthNames[viewMonth]" :prev="() => changeMonth(-1)" :next="() => changeMonth(1)" :open-picker="openPicker">
      <v-toolbar density="compact" flat color="transparent">
        <v-btn icon="mdi-chevron-left" variant="text" @click="changeMonth(-1)" />
        <v-spacer />
        <v-btn variant="text" class="text-h6" append-icon="mdi-menu-down" @click="openPicker">
          {{ monthNames[viewMonth] }}
        </v-btn>
        <v-spacer />
        <v-btn icon="mdi-chevron-right" variant="text" @click="changeMonth(1)" />
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
        }"
      >
        {{ label }}
      </div>
    </div>

    <!-- スワイプ可能なカレンダー本体 -->
    <div
      class="swipe-calendar__viewport"
      @touchstart.passive="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"
      @mousedown="onMouseDown"
    >
      <div
        class="swipe-calendar__track"
        :class="{ 'swipe-calendar__track--animating': isAnimating }"
        :style="trackStyle"
      >
        <div class="swipe-calendar__page">
          <MonthGrid
            :cells="prevCells"
            :selected-key="selectedKey"
            :max-events="maxEventsPerCell"
            @select="onSelectDate"
          >
            <template #day-number="sp"><slot name="day-number" v-bind="sp" /></template>
            <template #events="sp"><slot name="day-events" v-bind="sp" /></template>
          </MonthGrid>
        </div>
        <div class="swipe-calendar__page">
          <MonthGrid
            :cells="currentCells"
            :selected-key="selectedKey"
            :max-events="maxEventsPerCell"
            @select="onSelectDate"
          >
            <template #day-number="sp"><slot name="day-number" v-bind="sp" /></template>
            <template #events="sp"><slot name="day-events" v-bind="sp" /></template>
          </MonthGrid>
        </div>
        <div class="swipe-calendar__page">
          <MonthGrid
            :cells="nextCells"
            :selected-key="selectedKey"
            :max-events="maxEventsPerCell"
            @select="onSelectDate"
          >
            <template #day-number="sp"><slot name="day-number" v-bind="sp" /></template>
            <template #events="sp"><slot name="day-events" v-bind="sp" /></template>
          </MonthGrid>
        </div>
      </div>
    </div>

    <!-- 追加コンテンツ用slot（フッターなど） -->
    <slot name="footer" :year="viewYear" :month="viewMonth" />

    <!-- 日付詳細シート -->
    <DayDetailSheet
      v-model="detailOpen"
      :title="detailTitle"
      :events="selectedEvents"
      @add="openAdd"
    >
      <template #event-list="sp"><slot name="event-list" v-bind="sp" /></template>
    </DayDetailSheet>

    <!-- 予定追加シート -->
    <EventFormSheet
      v-if="enableAdd"
      v-model:open="addOpen"
      :date-label="detailTitle"
      :color-options="colorOptions"
      @submit="onSubmitEvent"
    >
      <template #form-fields="sp"><slot name="add-form-fields" v-bind="sp" /></template>
    </EventFormSheet>

    <!-- 年月ピッカー -->
    <MonthPickerSheet
      v-model:open="pickerOpen"
      :view-year="viewYear"
      :view-month="viewMonth"
      :month-names="monthNames"
      @select="onPickerSelect"
      @today="goToday"
    />
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
```

---

## `calendar/components/DayCell.vue`

```vue
<script setup lang="ts">
// DayCell.vue
// 月グリッドの1日分のセル。
// デフォルトの見た目を持ちつつ、slotで日付番号・イベント表示を差し替え可能。

import { computed } from 'vue'
import type { DayCellData, CalendarEvent } from '../types'

const props = defineProps<{
  cell: DayCellData
  /** 選択中の日付か */
  selected: boolean
  /** 1セルに表示する最大イベント数 */
  maxEvents?: number
}>()

const emit = defineEmits<{
  (e: 'select', key: string): void
}>()

const maxEvents = computed(() => props.maxEvents ?? 2)
const visibleEvents = computed(() => props.cell.events.slice(0, maxEvents.value))
const overflowCount = computed(() =>
  Math.max(0, props.cell.events.length - maxEvents.value),
)

function onClick(): void {
  if (!props.cell.isOtherMonth) emit('select', props.cell.key)
}

/**
 * イベントの色をCSSの背景色に解決する。
 * Vuetifyテーマカラー名なら var(--v-theme-xxx) を、
 * それ以外（#xxxやrgb）はそのまま使う。
 */
function resolveBg(ev: CalendarEvent): string {
  const c = ev.color
  if (!c) return 'rgb(var(--v-theme-primary))'
  // テーマカラー名っぽい（英字のみ）ならCSS変数に変換
  if (/^[a-z-]+$/i.test(c)) {
    return `rgb(var(--v-theme-${c}))`
  }
  return c
}
</script>

<template>
  <div
    class="day-cell"
    :class="{
      'day-cell--other': cell.isOtherMonth,
      'day-cell--selected': selected,
      'day-cell--sun': cell.dow === 0,
      'day-cell--sat': cell.dow === 6,
    }"
    @click="onClick"
  >
    <!-- 日付番号: slotで差し替え可能 -->
    <div class="day-cell__num-wrap">
      <slot name="day-number" :cell="cell">
        <span class="day-cell__num" :class="{ 'day-cell__num--today': cell.isToday }">
          {{ cell.day }}
        </span>
      </slot>
    </div>

    <!-- イベント表示: slotで差し替え可能 -->
    <div v-if="!cell.isOtherMonth" class="day-cell__events">
      <slot name="events" :cell="cell" :events="visibleEvents" :overflow="overflowCount">
        <div
          v-for="ev in visibleEvents"
          :key="ev.id"
          class="day-cell__event"
          :style="{ background: resolveBg(ev) }"
        >
          {{ ev.title }}
        </div>
        <div v-if="overflowCount > 0" class="day-cell__more">
          +{{ overflowCount }}件
        </div>
      </slot>
    </div>
  </div>
</template>

<style scoped>
.day-cell {
  position: relative;
  padding: 4px 2px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  transition: background 0.15s;
  overflow: hidden;
}

.day-cell:active {
  background: rgba(var(--v-theme-primary), 0.08);
}

.day-cell--other .day-cell__num {
  color: rgba(var(--v-theme-on-surface), 0.26);
}

.day-cell--selected {
  background: rgba(var(--v-theme-primary), 0.12);
}

.day-cell__num-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 2px;
  flex-shrink: 0;
}

.day-cell__num {
  font-size: 13px;
  font-weight: 500;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.day-cell__num--today {
  background: rgb(var(--v-theme-primary));
  color: rgb(var(--v-theme-on-primary));
}

.day-cell--sun .day-cell__num:not(.day-cell__num--today) {
  color: rgb(var(--v-theme-error));
}

.day-cell--sat .day-cell__num:not(.day-cell__num--today) {
  color: rgb(var(--v-theme-info));
}

.day-cell__events {
  display: flex;
  flex-direction: column;
  gap: 1px;
  overflow: hidden;
  padding: 0 1px;
  flex: 1;
  min-height: 0;
}

.day-cell__event {
  font-size: 9px;
  padding: 1px 3px;
  border-radius: 3px;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
  flex-shrink: 0;
}

.day-cell__more {
  font-size: 9px;
  color: rgba(var(--v-theme-on-surface), 0.6);
  padding-left: 3px;
  flex-shrink: 0;
}
</style>
```

---

## `calendar/components/MonthGrid.vue`

```vue
<script setup lang="ts">
// MonthGrid.vue
// 1ヶ月分の7×6グリッド。セルの描画は DayCell に委譲する。
// DayCell のslotを上位へ中継する。

import type { DayCellData } from '../types'
import DayCell from './DayCell.vue'

defineProps<{
  cells: DayCellData[]
  selectedKey: string
  maxEvents?: number
}>()

const emit = defineEmits<{
  (e: 'select', key: string): void
}>()
</script>

<template>
  <div class="month-grid">
    <DayCell
      v-for="cell in cells"
      :key="cell.key"
      :cell="cell"
      :selected="cell.key === selectedKey"
      :max-events="maxEvents"
      @select="emit('select', $event)"
    >
      <!-- DayCellのslotを上位へ中継 -->
      <template #day-number="slotProps">
        <slot name="day-number" v-bind="slotProps" />
      </template>
      <template #events="slotProps">
        <slot name="events" v-bind="slotProps" />
      </template>
    </DayCell>
  </div>
</template>

<style scoped>
.month-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-auto-rows: minmax(70px, 1fr);
  gap: 2px;
}
</style>
```

---

## `calendar/components/DayDetailSheet.vue`

```vue
<script setup lang="ts">
// DayDetailSheet.vue
// 日付タップ時に下から出る、その日の予定一覧ボトムシート。
// v-model で開閉を制御。

import { computed } from 'vue'
import type { CalendarEvent } from '../types'

const props = defineProps<{
  modelValue: boolean
  title: string
  events: CalendarEvent[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'add'): void
}>()

const isOpen = computed({
  get: () => props.modelValue,
  set: (v: boolean) => emit('update:modelValue', v),
})

function resolveColor(ev: CalendarEvent): string {
  return ev.color ?? 'primary'
}
</script>

<template>
  <v-bottom-sheet v-model="isOpen">
    <v-card>
      <v-toolbar :title="title" color="primary" density="comfortable">
        <template #append>
          <v-btn icon="mdi-close" @click="isOpen = false" />
        </template>
      </v-toolbar>

      <!-- 予定リスト: slotで差し替え可能 -->
      <slot name="event-list" :events="events">
        <v-list v-if="events.length" lines="two">
          <v-list-item
            v-for="ev in events"
            :key="ev.id"
            :title="ev.title"
            :subtitle="ev.time || '終日'"
          >
            <template #prepend>
              <v-avatar :color="resolveColor(ev)" size="small">
                <v-icon color="white" size="small">
                  {{ ev.icon || 'mdi-calendar' }}
                </v-icon>
              </v-avatar>
            </template>
          </v-list-item>
        </v-list>
        <v-card-text v-else class="text-center text-medium-emphasis py-8">
          予定はありません
        </v-card-text>
      </slot>

      <v-card-actions>
        <slot name="actions" :add="() => emit('add')">
          <v-btn
            block
            color="primary"
            variant="tonal"
            prepend-icon="mdi-plus"
            @click="emit('add')"
          >
            この日に予定を追加
          </v-btn>
        </slot>
      </v-card-actions>
    </v-card>
  </v-bottom-sheet>
</template>
```

---

## `calendar/components/EventFormSheet.vue`

```vue
<script setup lang="ts">
// EventFormSheet.vue
// 予定追加フォーム。ボトムシートなのでキーボードと被らない。
// v-model:open で開閉、保存時に submit イベントを emit する。

import { computed, reactive, watch } from 'vue'
import type { ColorOption, EventFormModel } from '../types'

const props = defineProps<{
  open: boolean
  /** 対象日付（表示用） */
  dateLabel?: string
  /** カラー選択肢。未指定ならデフォルト5色。 */
  colorOptions?: ColorOption[]
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'submit', value: EventFormModel): void
}>()

const DEFAULT_COLORS: ColorOption[] = [
  { title: 'プライマリ', value: 'primary' },
  { title: '成功', value: 'success' },
  { title: '注意', value: 'warning' },
  { title: 'パープル', value: 'purple' },
  { title: 'エラー', value: 'error' },
]

const colors = computed(() => props.colorOptions ?? DEFAULT_COLORS)

const isOpen = computed({
  get: () => props.open,
  set: (v: boolean) => emit('update:open', v),
})

const form = reactive<EventFormModel>({
  title: '',
  time: '',
  color: 'primary',
})

// 開くたびにフォームをリセット
watch(
  () => props.open,
  (opened) => {
    if (opened) {
      form.title = ''
      form.time = ''
      form.color = 'primary'
    }
  },
)

function save(): void {
  if (!form.title) return
  emit('submit', { ...form })
  isOpen.value = false
}
</script>

<template>
  <v-bottom-sheet v-model="isOpen">
    <v-card class="event-form-sheet">
      <v-toolbar :title="dateLabel ? `${dateLabel} の予定` : '新しい予定'" color="primary" density="comfortable">
        <template #append>
          <v-btn icon="mdi-close" @click="isOpen = false" />
        </template>
      </v-toolbar>

      <v-card-text>
        <slot name="form-fields" :form="form" :colors="colors">
          <v-text-field
            v-model="form.title"
            label="タイトル"
            variant="outlined"
            density="compact"
            autofocus
          />
          <v-text-field
            v-model="form.time"
            label="時刻 (例: 10:00)"
            variant="outlined"
            density="compact"
          />
          <v-select
            v-model="form.color"
            :items="colors"
            label="カラー"
            variant="outlined"
            density="compact"
          >
            <template #selection="{ item }">
              <v-chip :color="item.value" size="small">{{ item.title }}</v-chip>
            </template>
            <template #item="{ item, props: itemProps }">
              <v-list-item v-bind="itemProps" :title="undefined">
                <v-chip :color="item.value" size="small">{{ item.title }}</v-chip>
              </v-list-item>
            </template>
          </v-select>
        </slot>
      </v-card-text>

      <v-card-actions class="px-4 pb-4">
        <v-spacer />
        <v-btn @click="isOpen = false">キャンセル</v-btn>
        <v-btn color="primary" variant="flat" :disabled="!form.title" @click="save">
          保存
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-bottom-sheet>
</template>

<style scoped>
.event-form-sheet {
  padding-bottom: env(safe-area-inset-bottom);
}
</style>
```

---

## `calendar/components/MonthPickerSheet.vue`

```vue
<script setup lang="ts">
// MonthPickerSheet.vue
// 年送り + 月グリッドで一気に年月ジャンプできるボトムシート。

import { computed, ref, watch } from 'vue'

const props = defineProps<{
  open: boolean
  /** 現在表示中の年 */
  viewYear: number
  /** 現在表示中の月（0始まり） */
  viewMonth: number
  /** 月名ラベル（12個）。未指定なら日本語。 */
  monthNames?: string[]
}>()

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void
  (e: 'select', value: { year: number; month: number }): void
  (e: 'today'): void
}>()

const DEFAULT_MONTHS = [
  '1月', '2月', '3月', '4月', '5月', '6月',
  '7月', '8月', '9月', '10月', '11月', '12月',
]

const months = computed(() => props.monthNames ?? DEFAULT_MONTHS)

const isOpen = computed({
  get: () => props.open,
  set: (v: boolean) => emit('update:open', v),
})

// ピッカー内で操作中の年（確定するまで本体には反映しない）
const pickerYear = ref(props.viewYear)

watch(
  () => props.open,
  (opened) => {
    if (opened) pickerYear.value = props.viewYear
  },
)

function isSelected(monthIdx: number): boolean {
  return pickerYear.value === props.viewYear && monthIdx === props.viewMonth
}

function isCurrent(monthIdx: number): boolean {
  const t = new Date()
  return pickerYear.value === t.getFullYear() && monthIdx === t.getMonth()
}

function selectMonth(monthIdx: number): void {
  emit('select', { year: pickerYear.value, month: monthIdx })
  isOpen.value = false
}
</script>

<template>
  <v-bottom-sheet v-model="isOpen">
    <v-card>
      <v-toolbar color="primary" density="comfortable">
        <v-btn icon="mdi-chevron-left" @click="pickerYear--" />
        <v-toolbar-title class="text-center">{{ pickerYear }}年</v-toolbar-title>
        <v-btn icon="mdi-chevron-right" @click="pickerYear++" />
      </v-toolbar>

      <v-card-text>
        <div class="month-picker-grid">
          <v-btn
            v-for="(name, idx) in months"
            :key="idx"
            :variant="isSelected(idx) ? 'flat' : 'tonal'"
            :color="isSelected(idx) || isCurrent(idx) ? 'primary' : undefined"
            class="month-picker-btn"
            @click="selectMonth(idx)"
          >
            {{ name }}
          </v-btn>
        </div>
      </v-card-text>

      <v-card-actions class="px-4 pb-4">
        <v-btn variant="text" @click="emit('today'); isOpen = false">今月へ</v-btn>
        <v-spacer />
        <v-btn @click="isOpen = false">閉じる</v-btn>
      </v-card-actions>
    </v-card>
  </v-bottom-sheet>
</template>

<style scoped>
.month-picker-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  padding: 8px 0;
}

.month-picker-btn {
  height: 48px !important;
}
</style>
```

---

## 使用例: `App.vue`（参考）

```vue
<script setup lang="ts">
// App.vue
// SwipeCalendar の使用例。
// 親がイベントを管理し、月変更時にデータ取得、予定追加を受け取る流れを示す。

import { ref } from 'vue'
import SwipeCalendar from './SwipeCalendar.vue'
import type { EventMap, EventFormModel, DateKey, YearMonth } from './types'

// --- 親がイベントデータを管理する ---
const events = ref<EventMap>({})

// 日付キー生成ヘルパー
function dateKey(offset: number): DateKey {
  const d = new Date()
  d.setDate(d.getDate() + offset)
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

// アイコンのマッピング例
const iconByColor: Record<string, string> = {
  primary: 'mdi-account-group',
  success: 'mdi-silverware-fork-knife',
  warning: 'mdi-briefcase',
  purple: 'mdi-cake-variant',
  error: 'mdi-alert',
}

// サンプルデータ投入
function seed(): void {
  const map: EventMap = {}
  const add = (offset: number, title: string, color: string, time?: string) => {
    const key = dateKey(offset)
    if (!map[key]) map[key] = []
    map[key].push({
      id: `${key}-${map[key].length}`,
      title,
      color,
      time,
      icon: iconByColor[color],
    })
  }
  add(0, 'チームMTG', 'primary', '10:00 - 11:30')
  add(0, 'ランチ会', 'success', '12:00')
  add(1, '1on1', 'primary', '14:00')
  add(3, 'ワークショップ', 'warning', '09:00 - 12:00')
  add(-2, 'ディナー', 'purple', '19:00')
  add(7, 'レビュー', 'primary', '13:00')
  add(2, '歯医者', 'error', '16:00')
  add(2, 'ジム', 'success', '19:00')
  add(2, '読書会', 'purple', '21:00')
  events.value = map
}
seed()

// --- 月変更時: ここでAPIから該当月のイベントを取得する想定 ---
function onMonthChange(ym: YearMonth): void {
  console.log('月が変わった:', ym.year, ym.month + 1)
  // 例: fetchEvents(ym).then(data => { events.value = { ...events.value, ...data } })
}

// --- 予定追加を受け取る ---
function onAddEvent(payload: { date: DateKey; event: EventFormModel }): void {
  const { date, event } = payload
  const list = events.value[date] ?? []
  events.value = {
    ...events.value,
    [date]: [
      ...list,
      {
        id: `${date}-${list.length}-${Date.now()}`,
        title: event.title,
        time: event.time,
        color: event.color,
        icon: iconByColor[event.color],
      },
    ],
  }
  // 例: ここで API に保存リクエストを投げる
}

function onSelectDate(key: DateKey): void {
  console.log('選択:', key)
}
</script>

<template>
  <v-app>
    <v-main>
      <div class="calendar-container">
        <SwipeCalendar
          :events="events"
          @month-change="onMonthChange"
          @add-event="onAddEvent"
          @select-date="onSelectDate"
        />
      </div>
    </v-main>
  </v-app>
</template>

<style scoped>
.calendar-container {
  max-width: 480px;
  margin: 0 auto;
}
</style>
```

---

## ドキュメント


Vue 3 + Vuetify 3 向けの、スマホアプリのようにスワイプで月を切り替えられるカレンダーコンポーネント。

- 左右スワイプ / 矢印 / 年月ピッカーで月移動（指追従、`transform` ベースで軽量）
- 日付タップで予定一覧シート、予定追加シート（どちらも `v-bottom-sheet` でキーボードと被らない）
- イベントは親が `props.events` で管理（データ取得・保存は親の責任）
- 色は Vuetify テーマカラー（`primary` など）に追従。テーマを変えれば配色も自動で変わる
- 主要箇所を slot でカスタマイズ可能

## 依存

- Vue 3.4+
- Vuetify 3.5+
- `@mdi/font`（アイコン）

## ファイル構成

```
calendar/
├── types.ts                型定義
├── dateUtils.ts            日付計算の純粋関数
├── useCalendar.ts          状態・月グリッド生成のcomposable
├── useSwipe.ts             スワイプ処理のcomposable
├── SwipeCalendar.vue       メインコンポーネント
├── components/
│   ├── MonthGrid.vue       月グリッド
│   ├── DayCell.vue         日セル
│   ├── DayDetailSheet.vue  日付詳細シート
│   ├── EventFormSheet.vue  予定追加シート（独立）
│   └── MonthPickerSheet.vue 年月ピッカー
├── App.vue                 使用例
└── README.md
```

## 最小の使い方

```vue
<script setup lang="ts">
import { ref } from 'vue'
import SwipeCalendar from './calendar/SwipeCalendar.vue'
import type { EventMap } from './calendar/types'

const events = ref<EventMap>({
  '2026-05-01': [
    { id: 1, title: 'キックオフ', color: 'primary', time: '10:00' },
  ],
})
</script>

<template>
  <SwipeCalendar :events="events" />
</template>
```

## Props

| プロパティ | 型 | デフォルト | 説明 |
|---|---|---|---|
| `events` | `EventMap` | `{}` | 日付キー(`YYYY-MM-DD`)ごとのイベント配列 |
| `initialYear` | `number` | 今年 | 初期表示年 |
| `initialMonth` | `number` | 今月 | 初期表示月（0始まり） |
| `weekdayLabels` | `string[]` | `['日'..'土']` | 曜日ラベル（7個） |
| `monthNames` | `string[]` | `['1月'..'12月']` | 月名ラベル（12個） |
| `maxEventsPerCell` | `number` | `2` | 1セルに表示する最大イベント数 |
| `enableAdd` | `boolean` | `true` | 予定追加機能の有効化 |
| `colorOptions` | `ColorOption[]` | 5色 | 予定追加フォームのカラー選択肢 |

## Emits

| イベント | ペイロード | 説明 |
|---|---|---|
| `select-date` | `key: DateKey` | 日付が選択された |
| `month-change` | `{ year, month }` | 表示月が変わった（データ取得トリガに最適） |
| `add-event` | `{ date, event }` | 予定が追加された |

## イベント取得の連携例

`month-change` で隣接月を含めて取得しておくと、スワイプ時に既にデータがある状態にできる。

```ts
function onMonthChange({ year, month }: YearMonth) {
  // 前月・当月・翌月をまとめて取得し、events にマージ
  fetchRange(year, month).then((data) => {
    events.value = { ...events.value, ...data }
  })
}
```

## Slot 一覧

| slot名 | スコープ | 用途 |
|---|---|---|
| `header` | `{ year, month, openAdd, goToday }` | 上部バー全体を差し替え |
| `nav` | `{ monthLabel, prev, next, openPicker }` | 月ナビ行を差し替え |
| `day-number` | `{ cell }` | 日付番号の見た目 |
| `day-events` | `{ cell, events, overflow }` | セル内イベント表示 |
| `footer` | `{ year, month }` | カレンダー下に追加要素 |
| `event-list` | `{ events }` | 詳細シートの一覧 |
| `add-form-fields` | `{ form, colors }` | 追加フォームの入力欄 |

### slotの例: 日付番号に祝日マークを足す

```vue
<SwipeCalendar :events="events">
  <template #day-number="{ cell }">
    <span class="my-day">
      {{ cell.day }}
      <span v-if="isHoliday(cell.key)" class="holiday-dot" />
    </span>
  </template>
</SwipeCalendar>
```

## テーマカラー対応

イベントの `color` に Vuetify のテーマカラー名（`primary`, `success`, `error` など）を指定すると、
`rgb(var(--v-theme-xxx))` に解決されてテーマに追従する。`#ff0000` のような生のCSSカラーも指定可能。

今日マーカー・選択ハイライト・曜日色（日曜=`error`, 土曜=`info`）もすべてテーマ変数を参照しているので、
`createVuetify` のテーマ定義を変えるだけで全体の配色が変わる。

```ts
const vuetify = createVuetify({
  theme: {
    themes: {
      light: {
        colors: { primary: '#6750a4', error: '#b3261e', info: '#1d6fb8' },
      },
    },
  },
})
```

## パフォーマンスについて

- スワイプ中は `transform: translateX` のみ変更（GPU合成、レイアウト・ペイントなし）
- 前月・当月・翌月を常にプリレンダーしておくので、月切替時に新規DOM生成が発生しない
- `v-for` の key は実在日付の `YYYY-MM-DD` なので、月境界でのセル取り違えが起きない
- さらに最適化するなら、`useSwipe` の `onTouchMove` を `requestAnimationFrame` で間引くと低スペック端末で有利

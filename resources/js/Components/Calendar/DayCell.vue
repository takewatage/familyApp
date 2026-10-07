<script setup lang="ts">
// 月グリッドの1日分のセル。
// デフォルトの見た目を持ちつつ、slotで日付番号・イベント表示を差し替え可能。

import { computed } from 'vue'
import { resolveEventColor } from '@/Utils/calendarColor'
import type { DayCellData } from '@/Types/calendar'

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
const visibleEvents = computed(() =>
    props.cell.events.slice(0, maxEvents.value),
)
const overflowCount = computed(() =>
    Math.max(0, props.cell.events.length - maxEvents.value),
)

function onClick(): void {
    if (props.cell.isOtherMonth) {
        return
    }

    emit('select', props.cell.key)
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
        @click="onClick">
        <!-- 日付番号: slotで差し替え可能 -->
        <div class="day-cell__num-wrap">
            <slot
                name="day-number"
                :cell="cell">
                <span
                    class="day-cell__num"
                    :class="{ 'day-cell__num--today': cell.isToday }">
                    {{ cell.day }}
                </span>
            </slot>
        </div>

        <!-- イベント表示: slotで差し替え可能 -->
        <div
            v-if="!cell.isOtherMonth"
            class="day-cell__events">
            <slot
                name="events"
                :cell="cell"
                :events="visibleEvents"
                :overflow="overflowCount">
                <div
                    v-for="ev in visibleEvents"
                    :key="ev.id"
                    class="day-cell__event"
                    :style="{ background: resolveEventColor(ev) }">
                    {{ ev.title }}
                </div>
                <div
                    v-if="overflowCount > 0"
                    class="day-cell__more">
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
    /* overflow: hidden だと角丸で上の区切り線の両端が切れるため使わない。
       予定が多くても行が伸びないよう min-height: 0 にする（はみ出しは __events 側で隠す） */
    min-height: 0;
}

/* セル上の区切り線。テーマの border 色（ライト: 黒 / ダーク: 白、不透明度 0.12）に追従。
   角丸のセル背景と干渉しないよう疑似要素で引く（セルは overflow を隠さない） */
.day-cell::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    pointer-events: none;
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
    margin-bottom: 1px;
    flex-shrink: 0;
}

/* 予定を3件表示できるよう日付は小さめ */
.day-cell__num {
    font-size: 11px;
    font-weight: 500;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 今日: 背景と文字を反転（ダーク: 白地に黒文字 / ライト: 黒地に白文字） */
.day-cell__num--today {
    background: rgb(var(--v-theme-on-surface));
    color: rgb(var(--v-theme-surface));
}

/* 当月外のセルが今日の場合（例: 9月表示中の10/1）。当月外の文字色に負けて丸と同色になるのを防ぎ、丸ごと薄くする */
.day-cell--other .day-cell__num--today {
    color: rgb(var(--v-theme-surface));
    opacity: 0.4;
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
    font-weight: bold;
    white-space: nowrap;
    overflow: hidden;
    /* 「…」を付けると数文字しか見えないため、はみ出た分は切り捨てる */
    text-overflow: clip;
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

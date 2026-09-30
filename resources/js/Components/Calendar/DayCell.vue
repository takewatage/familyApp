<script setup lang="ts">
// 月グリッドの1日分のセル。
// デフォルトの見た目を持ちつつ、slotで日付番号・イベント表示を差し替え可能。

import { computed } from 'vue'
import type { CalendarEvent, DayCellData } from '@/Types/calendar'

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

/**
 * イベントの色をCSSの背景色に解決する。
 * Vuetifyテーマカラー名なら var(--v-theme-xxx) を、
 * それ以外（#xxxやrgb）はそのまま使う。
 */
function resolveBg(ev: CalendarEvent): string {
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
                    :style="{ background: resolveBg(ev) }">
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

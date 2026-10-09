<script setup lang="ts">
// 1ヶ月分の7×6グリッド。セルの描画は DayCell に委譲する。
// DayCell のslotを上位へ中継する。

import DayCell from '@/Components/Calendar/DayCell.vue'
import type { DayCellData } from '@/Types/calendar'

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
            @select="emit('select', $event)">
            <!-- DayCellのslotを上位へ中継 -->
            <template #day-number="slotProps">
                <slot
                    name="day-number"
                    v-bind="slotProps" />
            </template>
            <template #events="slotProps">
                <slot
                    name="events"
                    v-bind="slotProps" />
            </template>
        </DayCell>
    </div>
</template>

<style scoped>
.month-grid {
    display: grid;
    /* 1fr だと長い予定タイトル（折り返さない）の幅で列が広がるため、最小幅 0 で 7 列を等幅に固定する */
    grid-template-columns: repeat(7, minmax(0, 1fr));
    grid-auto-rows: minmax(70px, 1fr);
    /* 列の隙間をなくし、セル上下の区切り線を横一列につなげる */
    gap: 2px 0;
    /* 最終行の下線（各行の上線は DayCell 側） */
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>

<script setup lang="ts">
// 日付タップ時に下から出る、その日の予定一覧ボトムシート。
// v-model で開閉を制御。

import { computed } from 'vue'
import { resolveEventColor } from '@/Utils/calendarColor'
import type { CalendarEvent } from '@/Types/calendar'

const props = defineProps<{
    modelValue: boolean
    title: string
    events: CalendarEvent[]
    /** 「この日に予定を追加」ボタンを表示するか */
    enableAdd?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'add'): void
    /** 予定リストの項目をタップした */
    (e: 'select-event', event: CalendarEvent): void
}>()

const isOpen = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v),
})

</script>

<template>
    <v-bottom-sheet v-model="isOpen">
        <v-card>
            <v-toolbar
                :title="title"
                color="primary"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        @click="isOpen = false" />
                </template>
            </v-toolbar>

            <!-- 予定リスト: slotで差し替え可能 -->
            <slot
                name="event-list"
                :events="events">
                <v-list
                    v-if="events.length"
                    lines="two">
                    <v-list-item
                        v-for="ev in events"
                        :key="ev.id"
                        :title="ev.title"
                        :subtitle="ev.time || '終日'"
                        class="day-detail-sheet__event"
                        :style="{ borderLeftColor: resolveEventColor(ev) }"
                        @click="emit('select-event', ev)" />
                </v-list>
                <v-card-text
                    v-else
                    class="text-center text-medium-emphasis py-8">
                    予定はありません
                </v-card-text>
            </slot>

            <v-card-actions v-if="enableAdd">
                <slot
                    name="actions"
                    :add="() => emit('add')">
                    <v-btn
                        block
                        color="primary"
                        variant="tonal"
                        prepend-icon="mdi-plus"
                        @click="emit('add')">
                        この日に予定を追加
                    </v-btn>
                </slot>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style scoped>
/* 予定の色を左の縦線で示す */
.day-detail-sheet__event {
    border-left: 4px solid;
    margin: 2px 8px;
    border-radius: 4px;
}
</style>

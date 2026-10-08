<script setup lang="ts">
// 日付タップ時に下から出る、その日の予定一覧ボトムシート。
// v-model で開閉を制御。fullscreen を指定すると全画面のダイアログ（下から表示）にする。

import { computed } from 'vue'
import { VBottomSheet } from 'vuetify/components/VBottomSheet'
import { VDialog } from 'vuetify/components/VDialog'
import ParticipantAvatars from '@/Components/Calendar/ParticipantAvatars.vue'
import { resolveEventColor } from '@/Utils/calendarColor'
import type { CalendarEvent } from '@/Types/calendar'

const props = defineProps<{
    modelValue: boolean
    title: string
    events: CalendarEvent[]
    /** 「この日に予定を追加」ボタンを表示するか */
    enableAdd?: boolean
    /** 全画面で表示するか */
    fullscreen?: boolean
    /** 右下に予定追加ボタン（＋）を表示するか。押下で add を emit する */
    showAddFab?: boolean
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
    <component
        :is="fullscreen ? VDialog : VBottomSheet"
        v-model="isOpen"
        v-bind="fullscreen ? { fullscreen: true, transition: 'dialog-bottom-transition' } : {}">
        <!-- カード自体はスクロールさせず、予定リストだけをスクロールさせる（右下の＋ボタンを画面に固定するため） -->
        <v-card
            class="day-detail-sheet__card"
            :class="{ 'day-detail-sheet__card--fullscreen': fullscreen }">
            <v-toolbar
                :title="title"
                color="surface"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        @click="isOpen = false" />
                </template>
            </v-toolbar>

            <div
                class="day-detail-sheet__body"
                :class="{ 'day-detail-sheet__body--with-fab': showAddFab }">
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
                            @click="emit('select-event', ev)">
                            <template
                                v-if="ev.participants?.length"
                                #append>
                                <ParticipantAvatars :participants="ev.participants" />
                            </template>
                        </v-list-item>
                    </v-list>
                    <v-card-text
                        v-else
                        class="text-center text-medium-emphasis py-8">
                        予定はありません
                    </v-card-text>
                </slot>
            </div>

            <!-- ＋ボタン（showAddFab）と同時に出すと追加ボタンが重複するため、その場合は出さない -->
            <v-card-actions v-if="enableAdd && !showAddFab">
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

            <v-btn
                v-if="showAddFab"
                icon="mdi-plus"
                color="primary"
                size="large"
                class="day-detail-sheet__fab"
                aria-label="この日に予定を追加"
                @click="emit('add')" />
        </v-card>
    </component>
</template>

<style scoped>
.day-detail-sheet__card {
    position: relative;
    display: flex;
    flex-direction: column;
    max-height: 100%;
}

.day-detail-sheet__card--fullscreen {
    height: 100%;
}

.day-detail-sheet__body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

/* 右下の＋ボタンと最後の予定が重ならないよう余白を取る */
.day-detail-sheet__body--with-fab {
    padding-bottom: 88px;
}

.day-detail-sheet__fab {
    position: absolute;
    right: 16px;
    bottom: calc(16px + env(safe-area-inset-bottom));
}

/* 予定の色を左の縦線で示す */
.day-detail-sheet__event {
    border-left: 4px solid;
    margin: 2px 8px;
    border-radius: 4px;
}
</style>

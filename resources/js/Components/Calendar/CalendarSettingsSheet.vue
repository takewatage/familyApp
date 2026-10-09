<script setup lang="ts">
// カレンダー設定のボトムシート（画面の 90% の高さ）。設定項目は MENU に並べ、項目を足すときは MENU に追加する。
// 実際の変更（ラベルの編集画面・保存）は利用側が行い、このシートは選んだ操作を emit する。

import { computed, ref } from 'vue'
import LabelSelectSheet from '@/Components/Calendar/LabelSelectSheet.vue'
import { BIRTHDAY_COLOR } from '@/Constants/calendarColors'
import type { CalendarLabel } from '@/Types/calendar'
import type { CalendarSettingsResult } from '@/Types/dto.generated'

const props = defineProps<{
    open: boolean
    labels: CalendarLabel[]
    settings: CalendarSettingsResult
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    /** ラベルの編集画面を開く */
    (e: 'edit-labels'): void
    /** 誕生日のラベルを変更した（ラベルを使わない場合は null） */
    (e: 'update-birthday-label', labelId: string | null): void
}>()

const isOpen = computed({
    get: () => props.open,
    set: (v: boolean) => emit('update:open', v),
})

const birthdaySheetOpen = ref(false)

const birthdayLabel = computed(() => props.labels.find((l) => l.id === props.settings.birthdayLabelId))

interface MenuItem {
    key: string
    icon: string
    title: string
    subtitle: string
    /** 右端に出す色（ラベルの色など） */
    color?: string
    onClick: () => void
}

const MENU = computed<MenuItem[]>(() => [
    {
        key: 'labels',
        icon: 'mdi-tag-multiple-outline',
        title: 'ラベルの編集',
        subtitle: 'ラベルの名前・カラー・並び順を変更',
        onClick: () => emit('edit-labels'),
    },
    {
        key: 'birthday-label',
        icon: 'mdi-cake-variant-outline',
        title: '誕生日のラベル',
        subtitle: birthdayLabel.value?.name ?? 'ラベルを使わない（既定の色）',
        color: birthdayLabel.value?.color ?? BIRTHDAY_COLOR,
        onClick: () => (birthdaySheetOpen.value = true),
    },
])

function onBirthdayLabelSelect(labelId: string): void {
    emit('update-birthday-label', labelId || null)
}
</script>

<template>
    <!-- v-bottom-sheet は中身の高さに上限があるため、シート側で高さ（画面の 90%）を指定する -->
    <v-bottom-sheet
        v-model="isOpen"
        height="90vh"
        max-height="90vh">
        <v-card class="calendar-settings-sheet">
            <v-toolbar
                title="カレンダー設定"
                color="surface"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        aria-label="閉じる"
                        @click="isOpen = false" />
                </template>
            </v-toolbar>

            <v-list class="calendar-settings-sheet__list">
                <v-list-item
                    v-for="item in MENU"
                    :key="item.key"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    :subtitle="item.subtitle"
                    lines="two"
                    @click="item.onClick">
                    <template #append>
                        <span
                            v-if="item.color"
                            class="calendar-settings-sheet__color"
                            :style="{ background: item.color }" />
                        <v-icon icon="mdi-chevron-right" />
                    </template>
                </v-list-item>
            </v-list>
        </v-card>
    </v-bottom-sheet>

    <LabelSelectSheet
        v-model:open="birthdaySheetOpen"
        :model-value="settings.birthdayLabelId ?? ''"
        :labels="labels"
        title="誕生日のラベル"
        none-label="ラベルを使わない（既定の色）"
        hide-edit-button
        @update:model-value="onBirthdayLabelSelect" />
</template>

<style scoped>
/* 画面の 90% の高さで開き、項目が増えたらリストをスクロールする */
.calendar-settings-sheet {
    height: 100%;
    display: flex;
    flex-direction: column;
    padding-bottom: env(safe-area-inset-bottom);
}

.calendar-settings-sheet__list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

.calendar-settings-sheet__color {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 8px;
}
</style>

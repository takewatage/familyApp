<script setup lang="ts">
// カレンダー設定のボトムシート（画面の 90% の高さ）。設定項目は MENU に並べ、項目を足すときは MENU に追加する。
// 実際の変更（ラベルの編集画面・保存）は利用側が行い、このシートは選んだ操作を emit する。

import { computed, ref } from 'vue'
import { BIRTHDAY_COLOR } from '@/Constants/calendarColors'
import type { CalendarSettingsResult } from '@/Types/dto.generated'

const props = defineProps<{
    open: boolean
    settings: CalendarSettingsResult
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    /** ラベルの編集画面を開く */
    (e: 'edit-labels'): void
    /** 誕生日のカラーを変更した（既定の色に戻す場合は null） */
    (e: 'update-birthday-color', color: string | null): void
}>()

const isOpen = computed({
    get: () => props.open,
    set: (v: boolean) => emit('update:open', v),
})

// 誕生日のカラー（カラーピッカーのシート）
const birthdaySheetOpen = ref(false)
const pickerColor = ref<string>(BIRTHDAY_COLOR)

function openBirthdayColor(): void {
    pickerColor.value = props.settings.birthdayColor ?? BIRTHDAY_COLOR
    birthdaySheetOpen.value = true
}

function confirmBirthdayColor(): void {
    // カラーピッカーは #RRGGBB / #RRGGBBAA を返すことがあるため、#rrggbb にそろえる
    emit('update-birthday-color', pickerColor.value.slice(0, 7).toLowerCase())
    birthdaySheetOpen.value = false
}

function resetBirthdayColor(): void {
    emit('update-birthday-color', null)
    birthdaySheetOpen.value = false
}

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
        key: 'birthday-color',
        icon: 'mdi-cake-variant-outline',
        title: '誕生日のカラー',
        subtitle: props.settings.birthdayColor ? props.settings.birthdayColor.toUpperCase() : '既定の色',
        color: props.settings.birthdayColor ?? BIRTHDAY_COLOR,
        onClick: openBirthdayColor,
    },
])

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

    <v-bottom-sheet v-model="birthdaySheetOpen">
        <v-card class="birthday-color-sheet">
            <v-toolbar
                title="誕生日のカラー"
                color="surface"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        aria-label="閉じる"
                        @click="birthdaySheetOpen = false" />
                </template>
            </v-toolbar>

            <div class="d-flex justify-center px-4">
                <v-color-picker
                    v-model="pickerColor"
                    mode="hex"
                    :modes="['hex']"
                    elevation="0"
                    width="100%"
                    max-width="400" />
            </div>

            <v-card-actions class="px-4 pb-4">
                <v-btn
                    variant="text"
                    prepend-icon="mdi-restore"
                    @click="resetBirthdayColor">
                    既定の色に戻す
                </v-btn>
                <v-spacer />
                <v-btn
                    color="primary"
                    variant="flat"
                    @click="confirmBirthdayColor">
                    決定
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
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

.birthday-color-sheet {
    padding-bottom: env(safe-area-inset-bottom);
}

.calendar-settings-sheet__color {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 8px;
}
</style>

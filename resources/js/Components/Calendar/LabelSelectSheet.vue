<script setup lang="ts">
// 予定のラベルを選ぶボトムシート（単一選択。タップで選択して閉じる）。
// 一番下の「ラベル名やカラーを変更」で edit-labels を emit する（編集画面は利用側が開く）。
// noneLabel を渡すと先頭に「ラベルを使わない」選択肢を出す（選ぶと空文字を返す）。

import { computed } from 'vue'
import type { CalendarLabel } from '@/Types/calendar'

const props = defineProps<{
    open: boolean
    /** 選択中のラベル ID */
    modelValue: string
    labels: CalendarLabel[]
    /** 先頭に出す「ラベルなし」の選択肢の名前（未指定なら出さない） */
    noneLabel?: string
    /** シートの見出し */
    title?: string
    /** 「ラベル名やカラーを変更」ボタンを隠す */
    hideEditButton?: boolean
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'update:modelValue', value: string): void
    (e: 'edit-labels'): void
}>()

const isOpen = computed({
    get: () => props.open,
    set: (v: boolean) => emit('update:open', v),
})

function select(id: string): void {
    emit('update:modelValue', id)
    isOpen.value = false
}

function editLabels(): void {
    isOpen.value = false
    emit('edit-labels')
}
</script>

<template>
    <v-bottom-sheet v-model="isOpen">
        <v-card class="label-select-sheet">
            <v-toolbar
                :title="title ?? 'ラベルを選択'"
                color="surface"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        aria-label="閉じる"
                        @click="isOpen = false" />
                </template>
            </v-toolbar>

            <v-list
                class="label-select-sheet__list"
                role="radiogroup">
                <v-list-item
                    v-if="noneLabel"
                    role="radio"
                    :aria-checked="!modelValue"
                    @click="select('')">
                    <template #prepend>
                        <span class="label-select-sheet__swatch label-select-sheet__swatch--none" />
                    </template>
                    <v-list-item-title>{{ noneLabel }}</v-list-item-title>
                    <template #append>
                        <v-icon
                            v-if="!modelValue"
                            icon="mdi-check"
                            color="primary" />
                    </template>
                </v-list-item>
                <v-list-item
                    v-for="label in labels"
                    :key="label.id"
                    role="radio"
                    :aria-checked="label.id === modelValue"
                    @click="select(label.id)">
                    <template #prepend>
                        <span
                            class="label-select-sheet__swatch"
                            :style="{ background: label.color }" />
                    </template>
                    <v-list-item-title>{{ label.name }}</v-list-item-title>
                    <template #append>
                        <v-icon
                            v-if="label.id === modelValue"
                            icon="mdi-check"
                            color="primary" />
                    </template>
                </v-list-item>
            </v-list>

            <v-card-actions
                v-if="!hideEditButton"
                class="justify-center pb-4">
                <v-btn
                    variant="text"
                    size="small"
                    color="medium-emphasis"
                    prepend-icon="mdi-pencil-outline"
                    @click="editLabels">
                    ラベル名やカラーを変更
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style scoped>
.label-select-sheet {
    padding-bottom: env(safe-area-inset-bottom);
}

.label-select-sheet__list {
    max-height: 60vh;
    overflow-y: auto;
}

.label-select-sheet__swatch--none {
    border: 2px dashed rgba(var(--v-theme-on-surface), 0.38);
}

.label-select-sheet__swatch {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    margin-right: 16px;
}
</style>

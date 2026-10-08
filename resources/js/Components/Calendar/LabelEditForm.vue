<script setup lang="ts">
// ラベルの編集画面（フルスクリーンダイアログ用）。並び替え・名前の変更・カラーの変更ができる。
// useDialogService で開き、保存時に onClose(ラベル一覧) で結果を返す。保存（永続化）は利用側の責任。

import { computed, reactive, ref } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { CALENDAR_COLORS } from '@/Constants/calendarColors'
import { foregroundOn } from '@/Utils/calendarColor'
import type { CalendarLabel } from '@/Types/calendar'

const props = defineProps<{
    labels: CalendarLabel[]
    onClose?: (result?: CalendarLabel[]) => void
}>()

const items = ref<CalendarLabel[]>(props.labels.map((l) => ({ ...l })))

const submitted = ref(false)

// カラー選択シート
const colorSheet = reactive<{ open: boolean; targetId: string | null }>({ open: false, targetId: null })

const colorTarget = computed(() => items.value.find((l) => l.id === colorSheet.targetId))

function openColorSheet(id: string): void {
    colorSheet.targetId = id
    colorSheet.open = true
}

function selectColor(color: string): void {
    if (colorTarget.value) {
        colorTarget.value.color = color
    }

    colorSheet.open = false
}

function nameError(label: CalendarLabel): string | undefined {
    return submitted.value && !label.name.trim() ? 'ラベル名を入力してください' : undefined
}

function save(): void {
    submitted.value = true

    if (items.value.some((l) => !l.name.trim())) {
        return
    }

    props.onClose?.(items.value.map((l) => ({ ...l, name: l.name.trim() })))
}
</script>

<template>
    <v-card
        class="pa-4"
        elevation="0">
        <p class="text-caption text-medium-emphasis mb-3">
            左のつまみで並び替え、色をタップしてカラーを変更できます。
        </p>

        <VueDraggable
            v-model="items"
            :animation="200"
            handle=".label-edit-form__handle">
            <div
                v-for="label in items"
                :key="label.id"
                class="label-edit-form__row">
                <v-icon
                    class="label-edit-form__handle"
                    color="grey"
                    aria-label="並び替え">
                    mdi-drag
                </v-icon>
                <button
                    type="button"
                    class="label-edit-form__swatch"
                    :style="{ background: label.color }"
                    :aria-label="`${label.name}のカラーを変更`"
                    @click="openColorSheet(label.id)" />
                <v-text-field
                    :model-value="label.name"
                    :aria-label="'ラベル名'"
                    variant="outlined"
                    density="compact"
                    hide-details="auto"
                    maxlength="20"
                    :error-messages="nameError(label)"
                    @update:model-value="(v: string | null) => (label.name = v ?? '')" />
            </div>
        </VueDraggable>

        <v-btn
            color="primary"
            variant="flat"
            size="large"
            block
            class="mt-6"
            @click="save">
            保存
        </v-btn>

        <v-bottom-sheet v-model="colorSheet.open">
            <v-card class="label-edit-form__color-sheet">
                <v-toolbar
                    :title="colorTarget ? `${colorTarget.name} のカラー` : 'カラー'"
                    color="surface"
                    density="comfortable">
                    <template #append>
                        <v-btn
                            icon="mdi-close"
                            aria-label="閉じる"
                            @click="colorSheet.open = false" />
                    </template>
                </v-toolbar>
                <div
                    class="label-edit-form__palette"
                    role="radiogroup">
                    <button
                        v-for="c in CALENDAR_COLORS"
                        :key="c.value"
                        type="button"
                        role="radio"
                        class="label-edit-form__palette-item"
                        :class="{ 'is-selected': colorTarget?.color === c.value }"
                        :style="{ background: c.value }"
                        :aria-label="c.name"
                        :aria-checked="colorTarget?.color === c.value"
                        @click="selectColor(c.value)">
                        <v-icon
                            v-if="colorTarget?.color === c.value"
                            icon="mdi-check"
                            :color="foregroundOn(c.value)"
                            size="18" />
                    </button>
                </div>
            </v-card>
        </v-bottom-sheet>
    </v-card>
</template>

<style scoped>
.label-edit-form__row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.label-edit-form__handle {
    cursor: grab;
    touch-action: none;
}

.label-edit-form__swatch {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
}

.label-edit-form__color-sheet {
    padding-bottom: env(safe-area-inset-bottom);
}

.label-edit-form__palette {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(44px, 1fr));
    gap: 12px;
    padding: 16px;
}

.label-edit-form__palette-item {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.label-edit-form__palette-item.is-selected {
    box-shadow:
        0 0 0 2.5px rgb(var(--v-theme-surface)),
        0 0 0 4.5px rgba(0, 0, 0, 0.4);
}
</style>

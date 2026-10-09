<script setup lang="ts">
// 時刻の入力欄（DatePickerDialog と同じ見た目の読み取り専用欄）。タップで Vuetify の VTimePicker をダイアログで開く。
// v-model は 'HH:mm'（未入力は空文字）。時→分の順に選び、「OK」で確定する。

import { ref } from 'vue'

const props = defineProps<{
    modelValue?: string | null
    label?: string
    placeholder?: string
    errorMessages?: string | string[]
    clearable?: boolean
    density?: 'default' | 'comfortable' | 'compact'
    /** 入力欄の下のメッセージ領域（v-text-field の hide-details と同じ） */
    hideDetails?: boolean | 'auto'
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const dialog = ref(false)
const pickerTime = ref<string | null>(null)
const viewMode = ref<'hour' | 'minute'>('hour')

function openDialog(): void {
    pickerTime.value = props.modelValue || null
    viewMode.value = 'hour'
    dialog.value = true
}

function confirm(): void {
    if (pickerTime.value) {
        emit('update:modelValue', pickerTime.value)
    }

    dialog.value = false
}

function clear(): void {
    emit('update:modelValue', '')
    dialog.value = false
}
</script>

<template>
    <!-- 利用側の class（幅の指定など）が効くよう、1 つの要素で包む -->
    <div class="time-picker-dialog">
        <v-text-field
            :model-value="modelValue ?? ''"
            :label="label"
            :placeholder="placeholder"
            :persistent-placeholder="!!placeholder"
            :error-messages="errorMessages"
            :density="density ?? 'comfortable'"
            :hide-details="hideDetails"
            variant="outlined"
            readonly
            prepend-inner-icon="mdi-clock-outline"
            @click="openDialog"
            @click:prepend-inner="openDialog" />

        <v-dialog
            v-model="dialog"
            max-width="360">
            <v-card>
                <v-time-picker
                    v-model="pickerTime"
                    v-model:view-mode="viewMode"
                    :title="label ?? '時刻を選択'"
                    format="24hr"
                    color="primary"
                    class="mx-auto" />
                <v-card-actions>
                    <v-btn
                        v-if="clearable"
                        variant="text"
                        color="error"
                        @click="clear">
                        クリア
                    </v-btn>
                    <v-spacer />
                    <v-btn
                        variant="text"
                        @click="dialog = false">
                        キャンセル
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        :disabled="!pickerTime"
                        @click="confirm">
                        OK
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup lang="ts">
// 予定の編集フォーム（フルスクリーンダイアログ用）。
// useDialogService で開き、保存時に onClose(入力値) で結果を返す。
// 保存（永続化）はこのコンポーネントでは行わない。結果を受け取った利用側の責任。

import { computed, reactive, ref } from 'vue'
import DatePickerDialog from '@/Components/Common/DatePickerDialog.vue'
import ColorChipSelect from '@/Components/Common/ColorChipSelect.vue'
import { todayKey } from '@/Utils/calendarDate'
import type { EventEditModel } from '@/Types/calendar'

/** DayCell がテーマカラー名を CSS 変数に変換するため、テーマに存在する色名のみ使う */
const DEFAULT_COLORS = ['primary', 'secondary', 'success', 'info', 'warning', 'error']

const props = defineProps<{
    /** 初期値（新規追加時は開始日・終了日などを渡す） */
    initial?: Partial<EventEditModel>
    /** カラー選択肢（テーマカラー名） */
    colors?: string[]
    onClose?: (result?: EventEditModel) => void
}>()

const form = reactive<EventEditModel>({
    title: '',
    allDay: true,
    startDate: todayKey(),
    endDate: todayKey(),
    startTime: '',
    endTime: '',
    color: 'primary',
    ...props.initial,
})

const submitted = ref(false)

// 開始日を終了日より後にしたら、終了日を開始日に合わせる
function onStartDateChange(value: string | null): void {
    form.startDate = value ?? todayKey()

    if (form.endDate < form.startDate) {
        form.endDate = form.startDate
    }
}

const errors = computed(() => {
    const result: Partial<Record<keyof EventEditModel, string>> = {}

    if (!form.title.trim()) {
        result.title = 'タイトルを入力してください'
    }

    if (form.endDate < form.startDate) {
        result.endDate = '終了日は開始日以降にしてください'
    }

    if (!form.allDay) {
        if (!form.startTime) {
            result.startTime = '開始時刻を選択してください'
        }

        // 同じ日の予定で終了時刻が開始時刻より前
        if (form.endTime && form.startDate === form.endDate && form.endTime <= form.startTime) {
            result.endTime = '終了時刻は開始時刻より後にしてください'
        }
    }

    return result
})

const hasErrors = computed(() => Object.keys(errors.value).length > 0)

function save(): void {
    submitted.value = true

    if (hasErrors.value) {
        return
    }

    props.onClose?.({
        ...form,
        title: form.title.trim(),
        startTime: form.allDay ? '' : form.startTime,
        endTime: form.allDay ? '' : form.endTime,
    })
}

/** 送信を試みた後だけエラーを表示する */
function errorOf(key: keyof EventEditModel): string | undefined {
    return submitted.value ? errors.value[key] : undefined
}
</script>

<template>
    <v-card
        class="pa-4"
        elevation="0">
        <v-text-field
            v-model="form.title"
            label="タイトル"
            placeholder="予定を入力..."
            variant="outlined"
            density="comfortable"
            autofocus
            clearable
            :error-messages="errorOf('title')" />

        <v-switch
            v-model="form.allDay"
            label="終日"
            color="primary"
            density="compact"
            hide-details
            class="mb-2" />

        <div class="event-edit-form__row">
            <DatePickerDialog
                :model-value="form.startDate"
                label="開始日"
                density="comfortable"
                @update:model-value="onStartDateChange" />
            <v-text-field
                v-if="!form.allDay"
                v-model="form.startTime"
                label="開始時刻"
                type="time"
                variant="outlined"
                density="comfortable"
                :error-messages="errorOf('startTime')" />
        </div>

        <div class="event-edit-form__row">
            <DatePickerDialog
                v-model="form.endDate"
                label="終了日"
                density="comfortable"
                :error-messages="errorOf('endDate')" />
            <v-text-field
                v-if="!form.allDay"
                v-model="form.endTime"
                label="終了時刻（任意）"
                type="time"
                variant="outlined"
                density="comfortable"
                :error-messages="errorOf('endTime')" />
        </div>

        <p class="text-caption text-medium-emphasis mb-2">カラー</p>
        <ColorChipSelect
            v-model="form.color"
            :colors="colors ?? DEFAULT_COLORS" />

        <v-btn
            color="primary"
            variant="flat"
            size="large"
            block
            class="mt-6"
            @click="save">
            保存
        </v-btn>
    </v-card>
</template>

<style scoped>
/* 日付と時刻を横並び（時刻がない終日は日付が全幅） */
.event-edit-form__row {
    display: flex;
    gap: 8px;
}

.event-edit-form__row > * {
    flex: 1;
    min-width: 0;
}
</style>

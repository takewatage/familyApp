<script setup lang="ts">
// 予定の編集フォーム（フルスクリーンダイアログ用）。
// useDialogService で開き、保存時に onClose(入力値) で結果を返す。
// 保存（永続化）はこのコンポーネントでは行わない。結果を受け取った利用側の責任。

import { computed, reactive, ref } from 'vue'
import DatePickerDialog from '@/Components/Common/DatePickerDialog.vue'
import LabelEditForm from '@/Components/Calendar/LabelEditForm.vue'
import LabelSelectSheet from '@/Components/Calendar/LabelSelectSheet.vue'
import ParticipantAvatars from '@/Components/Calendar/ParticipantAvatars.vue'
import ParticipantSelectSheet from '@/Components/Calendar/ParticipantSelectSheet.vue'
import { todayKey } from '@/Utils/calendarDate'
import { useDialogService } from '@/Composables/Common/useDialogService'
import type { CalendarLabel, CalendarParticipant, EventEditModel } from '@/Types/calendar'

const props = defineProps<{
    /** 初期値（新規追加時は開始日・終了日などを渡す） */
    initial?: Partial<EventEditModel>
    /** ラベルの選択肢 */
    labels: CalendarLabel[]
    /** ラベルを編集（名前・カラー・並び順）したときに呼ぶ。利用側でラベル一覧を更新する */
    onLabelsChange?: (labels: CalendarLabel[]) => void
    /** 参加者の選択肢（家族メンバー）。空なら参加者欄を出さない */
    participants?: CalendarParticipant[]
    onClose?: (result?: EventEditModel) => void
}>()

const form = reactive<EventEditModel>({
    title: '',
    allDay: true,
    startDate: todayKey(),
    endDate: todayKey(),
    startTime: '',
    endTime: '',
    labelId: props.labels[0]?.id ?? '',
    participantIds: [],
    ...props.initial,
})

const participantSheetOpen = ref(false)

// ラベル（編集画面で変更したらこのフォームの表示にも反映する）
const labelList = ref<CalendarLabel[]>([...props.labels])
const labelSheetOpen = ref(false)
const selectedLabel = computed(() => labelList.value.find((l) => l.id === form.labelId))

const dialogService = useDialogService()

async function openLabelEditor(): Promise<void> {
    const dialog = dialogService.open<CalendarLabel[]>({
        component: LabelEditForm,
        props: { labels: labelList.value },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: { title: 'ラベルの編集' },
    })

    const result = await dialog.afterClosed()

    if (!result) {
        return
    }

    labelList.value = result
    props.onLabelsChange?.(result)
}

const selectedParticipants = computed(() =>
    (props.participants ?? []).filter((p) => form.participantIds.includes(p.id)),
)

const submitted = ref(false)

// clearable の × で null が入るため、空文字に正規化する
function onTitleChange(value: string | null): void {
    form.title = value ?? ''
}

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
            :model-value="form.title"
            label="タイトル"
            placeholder="予定を入力..."
            variant="outlined"
            density="comfortable"
            autofocus
            clearable
            :error-messages="errorOf('title')"
            @update:model-value="onTitleChange" />

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

        <template v-if="participants?.length">
            <p class="text-caption text-medium-emphasis mb-1">参加者</p>
            <v-card
                variant="outlined"
                class="event-edit-form__picker mb-4"
                role="button"
                aria-label="参加者を選択"
                @click="participantSheetOpen = true">
                <ParticipantAvatars
                    v-if="selectedParticipants.length"
                    :participants="selectedParticipants"
                    :max="5"
                    :size="28" />
                <span class="event-edit-form__ellipsis">
                    {{
                        selectedParticipants.length
                            ? selectedParticipants.map((p) => p.name).join('、')
                            : '参加者を選択'
                    }}
                </span>
                <v-icon
                    icon="mdi-chevron-right"
                    class="ml-auto" />
            </v-card>

            <ParticipantSelectSheet
                v-model:open="participantSheetOpen"
                v-model="form.participantIds"
                :participants="participants" />
        </template>

        <p class="text-caption text-medium-emphasis mb-1">ラベル</p>
        <v-card
            variant="outlined"
            class="event-edit-form__picker"
            role="button"
            aria-label="ラベルを選択"
            @click="labelSheetOpen = true">
            <span
                class="event-edit-form__label-swatch"
                :style="{ background: selectedLabel?.color ?? 'transparent' }" />
            <span class="event-edit-form__ellipsis">{{ selectedLabel?.name ?? 'ラベルを選択' }}</span>
            <v-icon
                icon="mdi-chevron-right"
                class="ml-auto" />
        </v-card>

        <LabelSelectSheet
            v-model:open="labelSheetOpen"
            v-model="form.labelId"
            :labels="labelList"
            @edit-labels="openLabelEditor" />

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

.event-edit-form__picker {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    min-height: 48px;
}

.event-edit-form__label-swatch {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.event-edit-form__ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>

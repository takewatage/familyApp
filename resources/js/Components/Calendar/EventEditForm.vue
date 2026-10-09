<script setup lang="ts">
// 予定の編集フォーム（フルスクリーンダイアログ用）。
// useDialogService で開き、保存時は onClose({ type: 'save', value }) 、削除時は onClose({ type: 'delete' }) を返す。
// 保存・削除（永続化）はこのコンポーネントでは行わない。結果を受け取った利用側の責任。

import { computed, reactive, ref } from 'vue'
import DatePickerDialog from '@/Components/Common/DatePickerDialog.vue'
import TimePickerDialog from '@/Components/Common/TimePickerDialog.vue'
import LabelEditForm from '@/Components/Calendar/LabelEditForm.vue'
import PickerField from '@/Components/Common/PickerField.vue'
import LabelSelectSheet from '@/Components/Calendar/LabelSelectSheet.vue'
import ParticipantAvatars from '@/Components/Calendar/ParticipantAvatars.vue'
import ParticipantSelectSheet from '@/Components/Calendar/ParticipantSelectSheet.vue'
import RecurrenceField from '@/Components/Calendar/RecurrenceField.vue'
import { todayKey } from '@/Utils/calendarDate'
import { adaptRecurrenceToStart } from '@/Utils/calendarRecurrence'
import { useDialogService } from '@/Composables/Common/useDialogService'
import type { CalendarLabel, CalendarParticipant, EventEditModel, EventEditResult } from '@/Types/calendar'

const props = defineProps<{
    /** 初期値（新規追加時は開始日・終了日などを渡す） */
    initial?: Partial<EventEditModel>
    /** ラベルの選択肢 */
    labels: CalendarLabel[]
    /**
     * ラベルを編集（名前・カラー・並び順）したときに呼ぶ。利用側で保存し、保存後のラベル一覧を返す
     * （保存に失敗したら undefined を返し、フォームの表示は変えない）
     */
    onLabelsChange?: (labels: CalendarLabel[]) => Promise<CalendarLabel[] | undefined>
    /** 削除ボタンを表示するか（編集時） */
    deletable?: boolean
    /** 参加者の選択肢（家族メンバー）。空なら参加者欄を出さない */
    participants?: CalendarParticipant[]
    onClose?: (result?: EventEditResult) => void
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
    memo: '',
    rrule: null,
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

    const saved = props.onLabelsChange ? await props.onLabelsChange(result) : result

    if (saved) {
        labelList.value = saved
    }
}

const selectedParticipants = computed(() =>
    (props.participants ?? []).filter((p) => form.participantIds.includes(p.id)),
)

const submitted = ref(false)

// clearable の × で null が入るため、空文字に正規化する
function onTitleChange(value: string | null): void {
    form.title = value ?? ''
}

// 開始日を終了日より後にしたら、終了日を開始日に合わせる。繰り返しのルールも新しい開始日に合わせる
function onStartDateChange(value: string | null): void {
    const previous = form.startDate

    form.startDate = value ?? todayKey()
    form.rrule = adaptRecurrenceToStart(form.rrule, previous, form.startDate)

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
        type: 'save',
        value: {
            ...form,
            title: form.title.trim(),
            memo: form.memo.trim(),
            startTime: form.allDay ? '' : form.startTime,
            endTime: form.allDay ? '' : form.endTime,
        },
    })
}

function remove(): void {
    props.onClose?.({ type: 'delete' })
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
        <!-- 入力欄は hide-details="auto"（エラー時だけメッセージ領域を出す）にし、欄の間隔は gap で揃える -->
        <div class="event-edit-form__fields">
            <v-text-field
                :model-value="form.title"
                label="タイトル"
                placeholder="予定を入力..."
                variant="outlined"
                density="comfortable"
                autofocus
                clearable
                hide-details="auto"
                :error-messages="errorOf('title')"
                @update:model-value="onTitleChange" />

            <div class="event-edit-form__switch-row">
                <span class="text-body-1">終日</span>
                <v-switch
                    v-model="form.allDay"
                    aria-label="終日"
                    color="primary"
                    density="compact"
                    hide-details
                    class="flex-grow-0" />
            </div>

            <div class="event-edit-form__row">
                <DatePickerDialog
                    :model-value="form.startDate"
                    label="開始日"
                    density="comfortable"
                    hide-details="auto"
                    @update:model-value="onStartDateChange" />
                <TimePickerDialog
                    v-if="!form.allDay"
                    v-model="form.startTime"
                    label="開始時刻"
                    hide-details="auto"
                    class="event-edit-form__time"
                    :error-messages="errorOf('startTime')" />
            </div>

            <div class="event-edit-form__row">
                <DatePickerDialog
                    v-model="form.endDate"
                    label="終了日"
                    density="comfortable"
                    hide-details="auto"
                    :error-messages="errorOf('endDate')" />
                <TimePickerDialog
                    v-if="!form.allDay"
                    v-model="form.endTime"
                    label="終了時刻"
                    placeholder="任意"
                    clearable
                    hide-details="auto"
                    class="event-edit-form__time"
                    :error-messages="errorOf('endTime')" />
            </div>

            <RecurrenceField
                v-model="form.rrule"
                :start-date="form.startDate" />

            <PickerField
                v-if="participants?.length"
                label="参加者"
                aria-label="参加者を選択"
                :placeholder="selectedParticipants.length ? undefined : '選択してください'"
                value=""
                @click="participantSheetOpen = true">
                <template
                    v-if="selectedParticipants.length"
                    #prepend>
                    <ParticipantAvatars
                        :participants="selectedParticipants"
                        :size="28" />
                </template>
            </PickerField>

            <PickerField
                label="ラベル"
                aria-label="ラベルを選択"
                placeholder="ラベルなし"
                :value="selectedLabel?.name ?? ''"
                @click="labelSheetOpen = true">
                <template
                    v-if="selectedLabel"
                    #prepend>
                    <span
                        class="event-edit-form__label-swatch"
                        :style="{ background: selectedLabel.color }" />
                </template>
            </PickerField>

            <v-textarea
                v-model="form.memo"
                label="メモ"
                variant="outlined"
                density="comfortable"
                rows="3"
                auto-grow
                maxlength="2000"
                hide-details="auto" />
        </div>

        <ParticipantSelectSheet
            v-if="participants?.length"
            v-model:open="participantSheetOpen"
            v-model="form.participantIds"
            :participants="participants" />

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

        <v-btn
            v-if="deletable"
            color="error"
            variant="text"
            block
            prepend-icon="mdi-delete-outline"
            class="mt-2"
            @click="remove">
            この予定を削除
        </v-btn>
    </v-card>
</template>

<style scoped>
/* フォームの欄を縦に並べ、間隔を揃える */
.event-edit-form__fields {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* 「終日」のラベルを左、スイッチを右端に置く（上下の余白は詰める） */
.event-edit-form__switch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: -8px 0;
    padding-left: 4px;
}

/* 日付と時刻を横並び（時刻がない終日は日付が全幅）。片方にエラーが出ても、もう片方の高さは変えない */
.event-edit-form__row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.event-edit-form__row > * {
    flex: 1;
    min-width: 0;
}

/* 時刻は「HH:mm」だけなので幅を抑え、日付（YYYY年M月D日）が見切れないようにする */
.event-edit-form__row > .event-edit-form__time {
    flex: 0 0 128px;
}

.event-edit-form__label-swatch {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

</style>

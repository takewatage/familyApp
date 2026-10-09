<script setup lang="ts">
// 予定の繰り返しの設定欄。タップでボトムシートを開き、頻度・曜日・終了条件を選ぶ。
// v-model は RRULE 文字列（繰り返さないなら null）。開始日に合わせて曜日・日付を決める。

import { computed, reactive, ref } from 'vue'
import DatePickerDialog from '@/Components/Common/DatePickerDialog.vue'
import PickerField from '@/Components/Common/PickerField.vue'
import {
    buildRecurrence,
    describeRecurrence,
    nthWeekdayOfMonth,
    parseRecurrence,
    weekdayLabel,
    type RecurrenceForm,
} from '@/Utils/calendarRecurrence'
import { fromDateKey } from '@/Utils/calendarDate'
import type { DateKey } from '@/Types/calendar'

const props = defineProps<{
    modelValue: string | null
    startDate: DateKey
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void
}>()

const FREQ_OPTIONS = [
    { value: 'none', title: '繰り返さない' },
    { value: 'DAILY', title: '毎日' },
    { value: 'WEEKLY', title: '毎週' },
    { value: 'MONTHLY', title: '毎月' },
    { value: 'YEARLY', title: '毎年' },
] as const

const sheetOpen = ref(false)
const form = reactive<RecurrenceForm>(parseRecurrence(props.modelValue, props.startDate))

const summary = computed(() => describeRecurrence(props.modelValue, props.startDate))
const start = computed(() => fromDateKey(props.startDate))
const nthLabel = computed(() => {
    const n = nthWeekdayOfMonth(props.startDate)

    return n === -1 ? '最終' : `第${n}`
})

function open(): void {
    Object.assign(form, parseRecurrence(props.modelValue, props.startDate))
    sheetOpen.value = true
}

function toggleWeekday(day: number): void {
    form.weekdays = form.weekdays.includes(day) ? form.weekdays.filter((d) => d !== day) : [...form.weekdays, day]
}

function confirm(): void {
    emit('update:modelValue', buildRecurrence(form, props.startDate))
    sheetOpen.value = false
}
</script>

<template>
    <PickerField
        label="繰り返し"
        aria-label="繰り返しを設定"
        :value="summary"
        @click="open">
        <template #prepend>
            <v-icon icon="mdi-repeat" />
        </template>
    </PickerField>

    <v-bottom-sheet v-model="sheetOpen">
        <v-card class="recurrence-field__sheet">
            <v-toolbar
                title="繰り返し"
                color="surface"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        aria-label="閉じる"
                        @click="sheetOpen = false" />
                </template>
            </v-toolbar>

            <v-card-text class="pt-0">
                <!-- column: 折り返して並べる（横スクロールの矢印を出さない） -->
                <v-chip-group
                    v-model="form.freq"
                    column
                    mandatory
                    selected-class="text-primary"
                    aria-label="繰り返しの頻度">
                    <v-chip
                        v-for="option in FREQ_OPTIONS"
                        :key="option.value"
                        :value="option.value"
                        variant="outlined">
                        {{ option.title }}
                    </v-chip>
                    <v-chip
                        v-if="form.freq === 'custom'"
                        value="custom"
                        variant="outlined">
                        カスタム
                    </v-chip>
                </v-chip-group>

                <template v-if="form.freq === 'WEEKLY'">
                    <p class="text-caption text-medium-emphasis mt-3 mb-1">曜日</p>
                    <div
                        class="recurrence-field__weekdays"
                        role="group"
                        aria-label="繰り返す曜日">
                        <v-btn
                            v-for="day in 7"
                            :key="day"
                            :variant="form.weekdays.includes(day - 1) ? 'flat' : 'outlined'"
                            :color="form.weekdays.includes(day - 1) ? 'primary' : undefined"
                            :aria-pressed="form.weekdays.includes(day - 1)"
                            size="small"
                            icon
                            @click="toggleWeekday(day - 1)">
                            {{ weekdayLabel(day - 1) }}
                        </v-btn>
                    </div>
                </template>

                <v-radio-group
                    v-if="form.freq === 'MONTHLY'"
                    v-model="form.monthlyBy"
                    color="primary"
                    class="mt-3"
                    hide-details>
                    <v-radio
                        value="date"
                        :label="`毎月 ${start.getDate()}日`" />
                    <v-radio
                        value="weekday"
                        :label="`毎月 ${nthLabel}${weekdayLabel(start.getDay())}曜日`" />
                </v-radio-group>

                <p
                    v-if="form.freq === 'custom'"
                    class="text-caption text-medium-emphasis mt-3">
                    この予定はアプリで編集できない繰り返し方です。頻度を選び直すと置き換わります。
                </p>

                <template v-if="form.freq !== 'none'">
                    <p class="text-caption text-medium-emphasis mt-4 mb-1">終了</p>
                    <v-radio-group
                        v-model="form.endType"
                        color="primary"
                        hide-details>
                        <v-radio
                            value="never"
                            label="終了しない" />
                        <v-radio
                            value="until"
                            label="日付を指定" />
                        <v-radio
                            value="count"
                            label="回数を指定" />
                    </v-radio-group>
                    <DatePickerDialog
                        v-if="form.endType === 'until'"
                        v-model="form.until"
                        label="終了日"
                        density="comfortable"
                        class="mt-2" />
                    <v-text-field
                        v-if="form.endType === 'count'"
                        v-model.number="form.count"
                        type="number"
                        min="1"
                        max="999"
                        label="回数"
                        suffix="回"
                        variant="outlined"
                        density="comfortable"
                        class="mt-2" />
                </template>
            </v-card-text>

            <v-card-actions class="px-4 pb-4">
                <v-btn
                    block
                    color="primary"
                    variant="flat"
                    size="large"
                    @click="confirm">
                    決定
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style scoped>
.recurrence-field__sheet {
    padding-bottom: env(safe-area-inset-bottom);
}

.recurrence-field__weekdays {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
</style>

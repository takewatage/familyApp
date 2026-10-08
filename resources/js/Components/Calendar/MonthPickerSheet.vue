<script setup lang="ts">
// 年送り + 月グリッドで一気に年月ジャンプできるボトムシート。

import { computed, ref, watch } from 'vue'

const props = defineProps<{
    open: boolean
    /** 現在表示中の年 */
    viewYear: number
    /** 現在表示中の月（0始まり） */
    viewMonth: number
    /** 月名ラベル（12個）。未指定なら日本語。 */
    monthNames?: string[]
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'select', value: { year: number; month: number }): void
    (e: 'today'): void
}>()

const DEFAULT_MONTHS = [
    '1月',
    '2月',
    '3月',
    '4月',
    '5月',
    '6月',
    '7月',
    '8月',
    '9月',
    '10月',
    '11月',
    '12月',
]

const months = computed(() => props.monthNames ?? DEFAULT_MONTHS)

const isOpen = computed({
    get: () => props.open,
    set: (v: boolean) => emit('update:open', v),
})

// ピッカー内で操作中の年（確定するまで本体には反映しない）
const pickerYear = ref(props.viewYear)

watch(
    () => props.open,
    (opened) => {
        if (!opened) {
            return
        }

        pickerYear.value = props.viewYear
    },
)

function isSelected(monthIdx: number): boolean {
    return pickerYear.value === props.viewYear && monthIdx === props.viewMonth
}

function isCurrent(monthIdx: number): boolean {
    const t = new Date()

    return pickerYear.value === t.getFullYear() && monthIdx === t.getMonth()
}

function selectMonth(monthIdx: number): void {
    emit('select', { year: pickerYear.value, month: monthIdx })
    isOpen.value = false
}

function goToday(): void {
    emit('today')
    isOpen.value = false
}
</script>

<template>
    <v-bottom-sheet v-model="isOpen">
        <v-card>
            <v-toolbar
                color="surface"
                density="comfortable">
                <v-btn
                    icon="mdi-chevron-left"
                    @click="pickerYear--" />
                <v-toolbar-title class="text-center">
                    {{ pickerYear }}年
                </v-toolbar-title>
                <v-btn
                    icon="mdi-chevron-right"
                    @click="pickerYear++" />
            </v-toolbar>

            <v-card-text>
                <div class="month-picker-grid">
                    <v-btn
                        v-for="(name, idx) in months"
                        :key="idx"
                        :variant="isSelected(idx) ? 'flat' : 'tonal'"
                        :color="
                            isSelected(idx) || isCurrent(idx)
                                ? 'primary'
                                : undefined
                        "
                        class="month-picker-btn"
                        @click="selectMonth(idx)">
                        {{ name }}
                    </v-btn>
                </div>
            </v-card-text>

            <v-card-actions class="px-4 pb-4">
                <v-btn
                    variant="text"
                    @click="goToday">
                    今月へ
                </v-btn>
                <v-spacer />
                <v-btn @click="isOpen = false">閉じる</v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style scoped>
.month-picker-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 8px 0;
}

.month-picker-btn {
    height: 48px !important;
}
</style>

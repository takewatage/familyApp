<script setup lang="ts">
// 予定の参加者を選ぶボトムシート（複数選択）。
// 開いた時点の選択状態をコピーして編集し、「決定」で update:modelValue する（閉じるだけなら反映しない）。

import { computed, ref, watch } from 'vue'
import type { CalendarParticipant } from '@/Types/calendar'

const props = defineProps<{
    open: boolean
    /** 選択中の参加者 ID */
    modelValue: string[]
    /** 選択肢（家族メンバー） */
    participants: CalendarParticipant[]
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'update:modelValue', value: string[]): void
}>()

const isOpen = computed({
    get: () => props.open,
    set: (v: boolean) => emit('update:open', v),
})

const selected = ref<string[]>([])

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            selected.value = [...props.modelValue]
        }
    },
    { immediate: true },
)

function toggle(id: string): void {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((v) => v !== id)
        : [...selected.value, id]
}

// 選択肢に無い ID（脱退したメンバー等）が混ざっていても数えないよう、選択肢側から判定する
const selectedCount = computed(() => props.participants.filter((p) => selected.value.includes(p.id)).length)

const allSelected = computed(
    () => props.participants.length > 0 && selectedCount.value === props.participants.length,
)

function toggleAll(): void {
    selected.value = allSelected.value ? [] : props.participants.map((p) => p.id)
}

function confirm(): void {
    // 選択肢の並び順で返す
    emit(
        'update:modelValue',
        props.participants.filter((p) => selected.value.includes(p.id)).map((p) => p.id),
    )
    isOpen.value = false
}
</script>

<template>
    <v-bottom-sheet v-model="isOpen">
        <v-card class="participant-select-sheet">
            <v-toolbar
                title="参加者を選択"
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
                v-if="participants.length"
                class="participant-select-sheet__list">
                <v-list-item @click="toggleAll">
                    <template #prepend>
                        <v-checkbox-btn
                            :model-value="allSelected"
                            color="primary"
                            tabindex="-1" />
                    </template>
                    <v-list-item-title class="font-weight-bold">全員</v-list-item-title>
                </v-list-item>

                <v-divider />

                <v-list-item
                    v-for="p in participants"
                    :key="p.id"
                    @click="toggle(p.id)">
                    <template #prepend>
                        <v-checkbox-btn
                            :model-value="selected.includes(p.id)"
                            color="primary"
                            tabindex="-1" />
                        <v-avatar
                            size="32"
                            color="secondary"
                            class="mr-3">
                            <v-img
                                v-if="p.avatarUrl"
                                :src="p.avatarUrl"
                                :alt="p.name" />
                            <span
                                v-else
                                class="text-white font-weight-bold">
                                {{ p.name.charAt(0) }}
                            </span>
                        </v-avatar>
                    </template>
                    <v-list-item-title>{{ p.name }}</v-list-item-title>
                </v-list-item>
            </v-list>
            <v-card-text
                v-else
                class="text-center text-medium-emphasis py-8">
                選べるメンバーがいません
            </v-card-text>

            <v-card-actions class="px-4 pb-4">
                <v-btn
                    block
                    color="primary"
                    variant="flat"
                    size="large"
                    @click="confirm">
                    決定（{{ selectedCount }}人）
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style scoped>
.participant-select-sheet {
    padding-bottom: env(safe-area-inset-bottom);
}

/* メンバーが多い場合はリストだけスクロールさせる */
.participant-select-sheet__list {
    max-height: 60vh;
    overflow-y: auto;
}
</style>

<script setup lang="ts">
// 予定追加フォーム。ボトムシートなのでキーボードと被らない。
// v-model:open で開閉、保存時に submit イベントを emit する。
//
// 保存（永続化）はこのコンポーネントでは行わない。submit を受け取った利用側の責任。

import { computed, reactive, watch } from 'vue'
import type { ColorOption, EventFormModel } from '@/Types/calendar'

const props = defineProps<{
    open: boolean
    /** 対象日付（表示用） */
    dateLabel?: string
    /** カラー選択肢。未指定ならデフォルト5色。 */
    colorOptions?: ColorOption[]
}>()

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void
    (e: 'submit', value: EventFormModel): void
}>()

const DEFAULT_COLORS: ColorOption[] = [
    { title: 'プライマリ', value: 'primary' },
    { title: '成功', value: 'success' },
    { title: '注意', value: 'warning' },
    { title: 'パープル', value: 'purple' },
    { title: 'エラー', value: 'error' },
]

const colors = computed(() => props.colorOptions ?? DEFAULT_COLORS)

const isOpen = computed({
    get: () => props.open,
    set: (v: boolean) => emit('update:open', v),
})

const form = reactive<EventFormModel>({
    title: '',
    time: '',
    color: 'primary',
})

// 開くたびにフォームをリセット
watch(
    () => props.open,
    (opened) => {
        if (!opened) {
            return
        }

        form.title = ''
        form.time = ''
        form.color = 'primary'
    },
)

function save(): void {
    if (!form.title) {
        return
    }

    emit('submit', { ...form })
    isOpen.value = false
}
</script>

<template>
    <v-bottom-sheet v-model="isOpen">
        <v-card class="event-form-sheet">
            <v-toolbar
                :title="dateLabel ? `${dateLabel} の予定` : '新しい予定'"
                color="surface"
                density="comfortable">
                <template #append>
                    <v-btn
                        icon="mdi-close"
                        @click="isOpen = false" />
                </template>
            </v-toolbar>

            <v-card-text>
                <slot
                    name="form-fields"
                    :form="form"
                    :colors="colors">
                    <v-text-field
                        v-model="form.title"
                        label="タイトル"
                        variant="outlined"
                        density="compact"
                        autofocus />
                    <v-text-field
                        v-model="form.time"
                        label="時刻 (例: 10:00)"
                        variant="outlined"
                        density="compact" />
                    <v-select
                        v-model="form.color"
                        :items="colors"
                        label="カラー"
                        variant="outlined"
                        density="compact">
                        <template #selection="{ item }">
                            <v-chip
                                :color="item.value"
                                size="small">
                                {{ item.title }}
                            </v-chip>
                        </template>
                        <template #item="{ item, props: itemProps }">
                            <v-list-item
                                v-bind="itemProps"
                                :title="undefined">
                                <v-chip
                                    :color="item.value"
                                    size="small">
                                    {{ item.title }}
                                </v-chip>
                            </v-list-item>
                        </template>
                    </v-select>
                </slot>
            </v-card-text>

            <v-card-actions class="px-4 pb-4">
                <v-spacer />
                <v-btn @click="isOpen = false">キャンセル</v-btn>
                <v-btn
                    color="primary"
                    variant="flat"
                    :disabled="!form.title"
                    @click="save">
                    保存
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style scoped>
.event-form-sheet {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>

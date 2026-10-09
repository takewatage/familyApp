<script setup lang="ts">
// 繰り返し予定の変更・削除の範囲（この予定のみ / これ以降 / すべて）を選ぶダイアログ。
// useDialogService で開き、選んだ範囲を onClose で返す（キャンセルは undefined）。

import type { DialogComponentProps } from '@/Composables/Common/useDialogService'
import type { RecurrenceScope } from '@/Api/calendarApi'

const props = withDefaults(
    defineProps<
        DialogComponentProps<RecurrenceScope> & {
            /** 'save' は保存、'delete' は削除 */
            mode?: 'save' | 'delete'
        }
    >(),
    { mode: 'save' },
)

const OPTIONS: { value: RecurrenceScope; title: string }[] = [
    { value: 'this', title: 'この予定のみ' },
    { value: 'following', title: 'これ以降の予定' },
    { value: 'all', title: 'すべての予定' },
]
</script>

<template>
    <v-card>
        <v-card-title class="text-subtitle-1 font-weight-bold pt-4">
            {{ mode === 'delete' ? '繰り返しの予定を削除' : '繰り返しの予定を変更' }}
        </v-card-title>
        <v-list>
            <v-list-item
                v-for="option in OPTIONS"
                :key="option.value"
                :title="option.title"
                :base-color="mode === 'delete' ? 'error' : undefined"
                @click="props.onClose(option.value)" />
        </v-list>
        <v-card-actions>
            <v-spacer />
            <v-btn
                variant="text"
                @click="props.onClose(undefined)">
                キャンセル
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

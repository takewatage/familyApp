<script setup lang="ts">
import { computed, ref } from 'vue'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import type { DialogComponentProps } from '@/Composables/Common/useDialogService'
import type { RegenerateFamilyCodeRequest } from '@/Types/dto.generated'

type Props = DialogComponentProps

const props = defineProps<Props>()
const snackbar = useSnackbar()

const expiresInOption = ref<'unlimited' | '24h' | '7d' | 'custom'>('unlimited')

const expiresInOptions = [
    { title: '無期限', value: 'unlimited' },
    { title: '24時間', value: '24h' },
    { title: '7日間', value: '7d' },
    { title: 'カスタム', value: 'custom' },
]

const customExpiresAt = ref<string>('')

const form = useInertiaForm<RegenerateFamilyCodeRequest>({
    expiresAt: null,
})

const computedExpiresAt = computed<string | null>(() => {
    if (expiresInOption.value === 'unlimited') {
        return null
    }

    if (expiresInOption.value === '24h') {
        return new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString()
    }

    if (expiresInOption.value === '7d') {
        return new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString()
    }

    return customExpiresAt.value || null
})

function handleSubmit() {
    form.expiresAt = computedExpiresAt.value

    form.post(route('family.code.regenerate'), {
        preserveScroll: true,
        only: ['family'],
        onSuccess: () => {
            snackbar.success('家族コードを再生成しました')
            props.onClose()
        },
        onError: () => snackbar.error('再生成に失敗しました'),
    })
}
</script>

<template>
    <v-card flat>
        <v-card-text>
            <p class="text-body-2 text-medium-emphasis mb-4">
                現在の家族コードを無効にして、新しいコードを発行します。
            </p>

            <v-select
                v-model="expiresInOption"
                label="有効期限"
                :items="expiresInOptions"
                variant="outlined"
                density="comfortable"/>

            <v-text-field
                v-if="expiresInOption === 'custom'"
                v-model="customExpiresAt"
                label="有効期限（日時）"
                type="datetime-local"
                variant="outlined"
                density="comfortable"
                class="mt-2"/>
        </v-card-text>
        <v-card-actions>
            <v-btn
                variant="text"
                @click="props.onClose()">
                キャンセル
            </v-btn>
            <v-spacer/>
            <v-btn
                color="warning"
                variant="flat"
                :loading="form.processing"
                @click="handleSubmit">
                再生成する
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

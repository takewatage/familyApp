<script setup lang="ts">
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import type { UserSettingsResult } from '@/Types/dto.generated'

const props = defineProps<{ settings: UserSettingsResult }>()

const snackbar = useSnackbar()

const form = useInertiaForm<UserSettingsResult>({
    theme: props.settings.theme,
})

const themeItems = [
    { title: 'システム設定に合わせる', value: 'system' },
    { title: 'ライト', value: 'light' },
    { title: 'ダーク', value: 'dark' },
]

function handleSubmit() {
    form.post(route('mypage.settings.update'), {
        onSuccess: () => snackbar.success('設定を保存しました'),
        onError: () => snackbar.error('設定の保存に失敗しました'),
    })
}
</script>

<template>
    <v-card class="mt-4">
        <v-card-title>設定</v-card-title>
        <v-card-text>
            <v-select
                v-model="form.theme"
                label="テーマ"
                :items="themeItems"
                variant="outlined"
                density="comfortable"
                :error-messages="form.errors.theme"/>
        </v-card-text>
        <v-card-actions>
            <v-spacer/>
            <v-btn
                color="primary"
                variant="flat"
                :loading="form.processing"
                @click="handleSubmit">
                設定を保存
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup lang="ts">
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { THEME_COLORS, THEME_COLOR_LABELS } from '@/Composables/Common/useAppTheme'
import type { UserSettingsResult } from '@/Types/dto.generated'

const props = defineProps<{ settings: UserSettingsResult }>()

const snackbar = useSnackbar()

const form = useInertiaForm<UserSettingsResult>({
    theme: props.settings.theme,
    themeColor: props.settings.themeColor,
})

const themeItems = [
    { title: 'システム設定に合わせる', value: 'system' },
    { title: 'ライト', value: 'light' },
    { title: 'ダーク', value: 'dark' },
]

const colorPresets = Object.entries(THEME_COLORS).map(([key, hex]) => ({
    key,
    hex,
    label: THEME_COLOR_LABELS[key],
}))

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

            <div class="mt-4">
                <p class="text-body-2 text-medium-emphasis mb-3">テーマカラー</p>
                <div class="d-flex gap-3 flex-wrap">
                    <button
                        v-for="preset in colorPresets"
                        :key="preset.key"
                        type="button"
                        class="color-chip"
                        :class="{ 'color-chip--selected': form.themeColor === preset.key }"
                        :style="{ backgroundColor: preset.hex }"
                        :aria-label="preset.label"
                        :title="preset.label"
                        @click="form.themeColor = preset.key"/>
                </div>
            </div>
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

<style scoped>
.color-chip {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.15s, border-color 0.15s;
    outline: none;
}

.color-chip:hover {
    transform: scale(1.15);
}

.color-chip--selected {
    border-color: rgba(0, 0, 0, 0.5);
    transform: scale(1.15);
    box-shadow: 0 0 0 2px white inset;
}
</style>

<script setup lang="ts">
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { THEMES } from '@/Composables/Common/useAppTheme'
import type { UserSettingsResult } from '@/Types/dto.generated'

const props = defineProps<{ settings: UserSettingsResult }>()

const snackbar = useSnackbar()

const form = useInertiaForm({
    theme: props.settings.theme,
    themeName: props.settings.themeName,
    footerItems: [...props.settings.footerItems],
})

const themeItems = [
    { title: 'システム設定に合わせる', value: 'system' },
    { title: 'ライト', value: 'light' },
    { title: 'ダーク', value: 'dark' },
]

function handleSubmit() {
    form.post(route('mypage.setting.theme-color.update'), {
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
                <v-row dense>
                    <v-col
                        v-for="preset in THEMES"
                        :key="preset.key"
                        cols="6">
                        <v-card
                            :variant="form.themeName === preset.key ? 'outlined' : 'elevated'"
                            :color="form.themeName === preset.key ? 'primary' : undefined"
                            elevation="1"
                            rounded="lg"
                            class="theme-card"
                            @click="form.themeName = preset.key">
                            <div class="theme-card__preview">
                                <div
                                    class="theme-card__primary"
                                    :style="{ background: preset.primary }"/>
                                <div
                                    class="theme-card__secondary"
                                    :style="{ background: preset.secondary }"/>
                            </div>
                            <div class="d-flex align-center justify-space-between pa-2">
                                <span class="text-body-2">{{ preset.label }}</span>
                                <v-icon
                                    v-if="form.themeName === preset.key"
                                    size="16"
                                    color="primary">
                                    mdi-check-circle
                                </v-icon>
                            </div>
                        </v-card>
                    </v-col>
                </v-row>
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
.theme-card {
    cursor: pointer;
    transition: transform 0.15s;
}

.theme-card:hover {
    transform: scale(1.02);
}

.theme-card__preview {
    height: 48px;
    display: flex;
    flex-direction: column;
    border-radius: 8px 8px 0 0;
    overflow: hidden;
}

.theme-card__primary {
    flex: 3;
}

.theme-card__secondary {
    flex: 2;
}
</style>

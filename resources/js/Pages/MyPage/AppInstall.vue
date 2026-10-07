<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { usePwaInstall } from '@/Composables/Common/usePwaInstall'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { APP_VERSION_LABEL } from '@/Constants/appVersion'
import type { FamilyPwaSettingsData } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<{ pwa: FamilyPwaSettingsData }>()
const pwa = computed(() => props.value.pwa)
const snackbar = useSnackbar()

const { platform, isStandalone, canPrompt, prompt } = usePwaInstall()

async function install() {
    const accepted = await prompt()

    if (accepted) {
        snackbar.success('ホーム画面に追加しました')
    }
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="8"
                md="6">
                <div class="d-flex align-center mb-4">
                    <v-btn
                        icon="mdi-arrow-left"
                        variant="text"
                        @click="router.visit(route('mypage.index'))"/>
                    <span class="text-h6 ml-2">アプリをインストール</span>
                </div>

                <v-card>
                    <v-card-text class="text-center">
                        <img
                            :src="pwa.iconApple"
                            class="app-icon"
                            alt="アプリアイコン"/>
                        <div class="text-h6 mt-2">{{ pwa.name }}</div>
                        <div class="text-caption text-medium-emphasis">{{ APP_VERSION_LABEL }}</div>
                    </v-card-text>
                </v-card>

                <v-card class="mt-4">
                    <v-card-text>
                        <!-- インストール済み（ホーム画面から起動中） -->
                        <template v-if="isStandalone">
                            <v-alert
                                type="success"
                                variant="tonal">
                                インストール済みです
                            </v-alert>
                        </template>

                        <!-- Android / PC Chrome 等: ブラウザのインストールダイアログを表示できる -->
                        <template v-else-if="canPrompt">
                            <p class="text-body-2 mb-4">
                                ホーム画面に追加すると、アプリのように全画面ですぐに開けます。
                            </p>
                            <v-btn
                                color="primary"
                                variant="flat"
                                block
                                size="large"
                                prepend-icon="mdi-cellphone-arrow-down"
                                @click="install">
                                ホーム画面に追加
                            </v-btn>
                        </template>

                        <!-- iOS / iPadOS: Safari の共有メニューから追加 -->
                        <template v-else-if="platform === 'ios'">
                            <p class="text-body-2 mb-2">Safari で次の手順で追加してください。</p>
                            <v-list density="compact">
                                <v-list-item prepend-icon="mdi-numeric-1-circle">
                                    画面下（iPad は右上）の共有ボタン
                                    <v-icon size="small">mdi-export-variant</v-icon>
                                    をタップ
                                </v-list-item>
                                <v-list-item prepend-icon="mdi-numeric-2-circle">
                                    「ホーム画面に追加」をタップ
                                </v-list-item>
                                <v-list-item prepend-icon="mdi-numeric-3-circle">
                                    右上の「追加」をタップ
                                </v-list-item>
                            </v-list>
                        </template>

                        <!-- その他: ブラウザメニューからの一般的な案内 -->
                        <template v-else>
                            <p class="text-body-2">
                                ブラウザのメニュー（
                                <v-icon size="small">mdi-dots-vertical</v-icon>
                                など）から「アプリをインストール」または「ホーム画面に追加」を選んでください。
                            </p>
                        </template>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped lang="scss">
.app-icon {
    width: 96px;
    height: 96px;
    border-radius: 22px;
    object-fit: cover;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}
</style>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import type { MyPageData } from '@/Types/dto.generated'
import EditProfileForm from '@/Components/MyPage/EditProfileForm.vue'
import { formatDate } from '@/Utils/dateFormatter'
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'
import { APP_VERSION_LABEL } from '@/Constants/appVersion'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<MyPageData>()
const { open } = useDialogService()
const { confirm } = useConfirmDialog()

async function logout(): Promise<void> {
    const ok = await confirm({
        title: 'ログアウトしますか？',
        confirmText: 'ログアウト',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.post(route('logout'))
}

/** プロフィールの表示項目（左にラベル、右に値） */
const profileRows = computed(() => [
    { label: 'ユーザー名', value: props.value.user.name },
    { label: 'メールアドレス', value: props.value.user.email },
    { label: '生年月日', value: formatDate(props.value.user.birthday) || '未設定' },
    { label: '登録日', value: formatDate(props.value.user.createdAt) },
])

const onEdit = async () => {
    open<MyPageData>({
        component: EditProfileForm,
        props: {
            name: props.value.user.name,
            birthday: props.value.user.birthday ?? null,
            avatar: props.value.user.avatar ?? null,
        },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: {
            title: 'プロフィール編集',
        },
    })
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="8"
                md="6">
                <v-card class="pa-4">
                    <div class="d-flex align-center justify-space-between mb-4">
                        <v-card-title class="text-h5 pa-0">マイページ</v-card-title>
                        <v-btn
                            icon="mdi-pencil"
                            variant="text"
                            @click="onEdit"/>
                    </div>

                    <v-card-text class="pa-0">
                        <div class="d-flex flex-column align-center mb-6">
                            <v-avatar
                                size="100"
                                color="primary"
                                class="mb-3">
                                <v-img
                                    v-if="props.user.avatar"
                                    :src="props.user.avatar.url"/>
                                <v-icon
                                    v-else
                                    icon="mdi-account"
                                    size="60"
                                    color="white"/>
                            </v-avatar>
                        </div>

                        <v-list>
                            <template
                                v-for="(row, i) in profileRows"
                                :key="row.label">
                                <v-divider v-if="i > 0"/>
                                <v-list-item>
                                    <div class="profile-row">
                                        <span class="profile-row__label">{{ row.label }}</span>
                                        <span class="profile-row__value">{{ row.value }}</span>
                                    </div>
                                </v-list-item>
                            </template>
                        </v-list>
                    </v-card-text>
                </v-card>
                <v-card class="mt-4">
                    <v-card-title>設定</v-card-title>
                    <v-list>
                        <v-list-item
                            prepend-icon="mdi-palette"
                            title="テーマカラー設定"
                            append-icon="mdi-chevron-right"
                            @click="router.visit(route('mypage.setting.theme-color.index'))"/>
                        <v-divider/>
                        <v-list-item
                            prepend-icon="mdi-view-grid-outline"
                            title="アプリショートカット設定"
                            subtitle="フッターに表示するアプリを設定"
                            append-icon="mdi-chevron-right"
                            @click="router.visit(route('mypage.footer-settings.index'))"/>
                    </v-list>
                </v-card>

                <v-card class="mt-4">
                    <v-card-title>家族設定</v-card-title>
                    <v-list>
                        <v-list-item
                            prepend-icon="mdi-home-edit"
                            title="家族設定変更"
                            append-icon="mdi-chevron-right"
                            @click="router.visit(route('family.settings.index'))"/>
                        <v-divider/>
                        <v-list-item
                            prepend-icon="mdi-account-group"
                            title="メンバー管理"
                            append-icon="mdi-chevron-right"
                            @click="router.visit(route('family.members.index'))"/>
                        <v-divider/>
                        <v-list-item
                            prepend-icon="mdi-swap-horizontal"
                            title="家族切り替え"
                            append-icon="mdi-chevron-right"
                            @click="router.visit(route('family.switch.index'))"/>
                    </v-list>
                </v-card>

                <v-card class="mt-4">
                    <v-card-title>アプリ</v-card-title>
                    <v-list>
                        <v-list-item
                            prepend-icon="mdi-cellphone-arrow-down"
                            title="アプリをインストール"
                            subtitle="ホーム画面に追加して使う"
                            append-icon="mdi-chevron-right"
                            @click="router.visit(route('mypage.app-install'))"/>
                        <v-divider/>
                        <v-list-item
                            prepend-icon="mdi-information-outline"
                            title="バージョン"
                            :subtitle="APP_VERSION_LABEL"/>
                    </v-list>
                </v-card>

                <v-btn
                    block
                    size="large"
                    variant="outlined"
                    color="error"
                    prepend-icon="mdi-logout"
                    class="mt-6 mb-4"
                    @click="logout">
                    ログアウト
                </v-btn>
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped>
/* プロフィール: 左にラベル、右に値（長いメールアドレスは省略表示） */
.profile-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.profile-row__label {
    flex-shrink: 0;
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
}

.profile-row__value {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-align: right;
}
</style>

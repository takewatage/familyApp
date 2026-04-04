<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useDialogService } from '@/Composables/Common/useDialogService'
import type { MyPageData } from '@/Types/dto.generated'
import EditProfileForm from '@/Components/MyPage/EditProfileForm.vue'
import { formatDate } from '@/Utils/dateFormatter'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: DokLayout })

const props = usePageProps<MyPageData>()
const { open } = useDialogService()

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

                        <v-list lines="two">
                            <v-list-item
                                prepend-icon="mdi-account"
                                title="ユーザー名"
                                :subtitle="props.user.name"/>
                            <v-divider/>
                            <v-list-item
                                prepend-icon="mdi-email"
                                title="メールアドレス"
                                :subtitle="props.user.email"/>
                            <v-divider/>
                            <v-list-item
                                prepend-icon="mdi-cake-variant"
                                title="生年月日"
                                :subtitle="formatDate(props.user.birthday) || '未設定'"/>
                            <v-divider/>
                            <v-list-item
                                prepend-icon="mdi-calendar"
                                title="登録日"
                                :subtitle="formatDate(props.user.createdAt)"/>
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
                            @click="router.visit(route('mypage.settings.index'))"/>
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
            </v-col>
        </v-row>
    </v-container>
</template>

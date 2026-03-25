<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { MyPageData } from '@/Types/dto.generated'
import EditProfileForm from '@/Components/MyPage/EditProfileForm.vue'
import { computed } from 'vue'
import { formatDate } from '@/Utils/dateFormatter'

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
            </v-col>
        </v-row>
    </v-container>
</template>

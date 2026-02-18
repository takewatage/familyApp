<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { MyPageData } from '@/Types/dto.generated'

defineOptions({ layout: DokLayout })

const props = usePageProps<MyPageData>()
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="8"
                md="6">
                <v-card class="pa-4">
                    <v-card-title class="text-h5 mb-4">マイページ</v-card-title>

                    <v-card-text>
                        <div class="d-flex flex-column align-center mb-6">
                            <v-avatar
                                size="100"
                                color="primary"
                                class="mb-3">
                                <v-img
                                    v-if="props.avatarUrl"
                                    :src="props.avatarUrl"
                                    alt="アバター" />
                                <v-icon
                                    v-else
                                    icon="mdi-account"
                                    size="60"
                                    color="white" />
                            </v-avatar>
                        </div>

                        <v-list lines="two">
                            <v-list-item
                                prepend-icon="mdi-account"
                                title="ユーザー名"
                                :subtitle="props.name" />
                            <v-divider />
                            <v-list-item
                                prepend-icon="mdi-email"
                                title="メールアドレス"
                                :subtitle="props.email" />
                            <v-divider />
                            <v-list-item
                                prepend-icon="mdi-calendar"
                                title="登録日"
                                :subtitle="props.createdAt" />
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

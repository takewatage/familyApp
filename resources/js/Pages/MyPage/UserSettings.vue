<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { router } from '@inertiajs/vue3'
import UserSettingsForm from '@/Components/MyPage/UserSettingsForm.vue'
import type { UserSettingsResult } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<UserSettingsResult>()
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
                    <span class="text-h6 ml-2">設定</span>
                </div>

                <UserSettingsForm :settings="props"/>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AuthCard from '@/Components/Auth/AuthCard.vue'
import type { InviteConfirmResult } from '@/Types/dto.generated'
import { computed } from 'vue'

defineOptions({ layout: GuestLayout })

const props = defineProps<InviteConfirmResult>()

const page = usePage()
const isLoggedIn = computed(() => !!page.props.auth?.user)

const form = useForm({})

const roleLabel = computed(() => {
    const labels: Record<string, string> = {
        parent: '保護者',
        child: '子ども',
        guest: 'メンバー',
    }
    return labels[props.inviteRole ?? 'guest'] ?? 'メンバー'
})

function joinFamily() {
    form.post(route('invite.join', { code: props.code }))
}
</script>

<template>
    <Head title="家族に参加"/>

    <AuthCard>
        <!-- 無効なコード -->
        <template v-if="props.error === 'invalid'">
            <div class="text-center">
                <v-icon
                    icon="mdi-alert-circle-outline"
                    size="48"
                    color="warning"
                    class="mb-4"/>
                <p class="text-h6 mb-2">この招待リンクは無効です</p>
                <p class="text-body-2 text-medium-emphasis mb-6">
                    招待コードが期限切れか、存在しないリンクです。<br>
                    招待者に新しいリンクを送ってもらってください。
                </p>
                <v-btn
                    variant="tonal"
                    :href="route('login')">
                    ログインページへ
                </v-btn>
            </div>
        </template>

        <!-- 有効なコード -->
        <template v-else>
            <div class="text-center mb-6">
                <v-icon
                    icon="mdi-home-heart"
                    size="48"
                    color="primary"
                    class="mb-4"/>
                <p class="text-h6 font-weight-bold mb-1">{{ props.familyName }}</p>
                <p class="text-body-2 text-medium-emphasis">
                    に参加しませんか？
                </p>
                <div class="d-flex justify-center gap-2 mt-2">
                    <v-chip
                        size="small"
                        variant="tonal"
                        prepend-icon="mdi-account-multiple">
                        現在 {{ props.memberCount }} 名
                    </v-chip>
                    <v-chip
                        size="small"
                        variant="tonal"
                        color="primary"
                        prepend-icon="mdi-shield-account">
                        {{ roleLabel }} として参加
                    </v-chip>
                </div>
            </div>

            <!-- 定員超過 -->
            <v-alert
                v-if="props.isFull"
                type="warning"
                variant="tonal"
                class="mb-4">
                この家族は定員に達しています
            </v-alert>

            <!-- 参加済み -->
            <template v-else-if="props.alreadyJoined">
                <v-alert
                    type="info"
                    variant="tonal"
                    class="mb-4">
                    すでにこの家族に参加しています
                </v-alert>
                <v-btn
                    block
                    color="primary"
                    variant="tonal"
                    :href="route('home')">
                    ホームへ
                </v-btn>
            </template>

            <!-- ログイン済み → 参加ボタン -->
            <template v-else-if="isLoggedIn">
                <v-btn
                    block
                    color="primary"
                    variant="flat"
                    size="large"
                    :loading="form.processing"
                    @click="joinFamily">
                    この家族に参加する
                </v-btn>
            </template>

            <!-- 未ログイン → 登録 / ログイン -->
            <template v-else>
                <v-btn
                    block
                    color="primary"
                    variant="flat"
                    size="large"
                    class="mb-3"
                    :href="route('register')">
                    新規登録して参加
                </v-btn>
                <v-btn
                    block
                    variant="tonal"
                    size="large"
                    :href="route('login')">
                    ログインして参加
                </v-btn>
            </template>
        </template>
    </AuthCard>
</template>

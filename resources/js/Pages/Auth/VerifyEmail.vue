<script setup lang="ts">
import { computed } from 'vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthCard from '@/Components/Auth/AuthCard.vue'

const props = defineProps<{
    status?: string
}>()

defineOptions({ layout: GuestLayout })

const form = useForm({})

const submit = () => {
    form.post(route('verification.send'))
}

const verificationLinkSent = computed(() => props.status === 'verification-link-sent')
</script>

<template>
    <Head title="Email Verification" />

    <AuthCard>
        <v-alert
            v-if="verificationLinkSent"
            color="success"
            class="mb-5">
            新しい確認リンクが、登録時に指定した電子メール アドレスに送信されました。
        </v-alert>
        <p>
            ご登録いただきありがとうございます! 始める前に、リンクをクリックしてメールアドレスを確認してください。
            <br />
            メールが届いていない場合は、再送信ボタンから再送信してください。
        </p>
        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <v-btn
                    block
                    type="submit"
                    class="mb-8"
                    size="large"
                    color="blue"
                    :disabled="form.processing"
                    :loading="form.processing">
                    再送信
                </v-btn>

                <v-btn
                    variant="outlined"
                    color="white"
                    class="mb-8"
                    size="large"
                    @click="
                        () => {
                            router.post('logout')
                        }
                    ">
                    ログアウト
                </v-btn>
            </div>
        </form>
    </AuthCard>
</template>

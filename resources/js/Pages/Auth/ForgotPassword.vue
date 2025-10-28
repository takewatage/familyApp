<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthCard from '@/Components/Auth/AuthCard.vue'

defineProps<{
    status?: string
}>()

defineOptions({ layout: GuestLayout })

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <Head title="パスワードリセット" />

    <auth-card>
        <v-alert
            v-if="status"
            color="success"
            class="mb-5">
            {{ status }}
        </v-alert>

        <p class="mb-4">パスワードを変更するには、アカウントに登録されたメールアドレスを入力してください。</p>
        <v-form @submit.prevent="submit">
            <div>
                <v-text-field
                    v-model="form.email"
                    autofocus
                    label="メールアドレス"
                    :error="form.errors.hasOwnProperty('email')"
                    :error-messages="form.errors.email || []"
                    density="compact"
                    placeholder="example.com"
                    prepend-inner-icon="mdi-email-outline"
                    variant="outlined"></v-text-field>
            </div>

            <div class="mt-2">
                <v-btn
                    type="submit"
                    block
                    class="mb-8"
                    color="blue"
                    size="large"
                    variant="tonal"
                    :disabled="form.processing"
                    :loading="form.processing">
                    パスワードリセットリンク送信
                </v-btn>
            </div>
        </v-form>
    </auth-card>
</template>

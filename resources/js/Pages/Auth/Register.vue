<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthCard from '@/Components/Auth/AuthCard.vue'

defineOptions({ layout: GuestLayout })

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}
</script>

<template>
    <Head title="Register" />

    <AuthCard card-title="アカウントを作成">
        <form @submit.prevent="submit">
            <div>
                <v-text-field
                    v-model="form.name"
                    label="名前"
                    :error="form.errors.hasOwnProperty('name')"
                    :error-messages="form.errors.name || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-account-outline"></v-text-field>
            </div>

            <div class="mt-4">
                <v-text-field
                    v-model="form.email"
                    label="メールアドレス"
                    type="email"
                    :error="form.errors.hasOwnProperty('email')"
                    density="compact"
                    variant="outlined"
                    :error-messages="form.errors.email || []"
                    prepend-inner-icon="mdi-email-outline"></v-text-field>
            </div>

            <div class="mt-4">
                <v-text-field
                    v-model="form.password"
                    label="パスワード"
                    :error="form.errors.hasOwnProperty('password')"
                    :error-messages="form.errors.password || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock-outline"></v-text-field>
            </div>

            <div class="mt-4">
                <v-text-field
                    v-model="form.password_confirmation"
                    label="パスワード（確認）"
                    :error="form.errors.hasOwnProperty('password_confirmation')"
                    :error-messages="form.errors.password_confirmation || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock-outline"></v-text-field>
            </div>

            <v-btn
                type="submit"
                block
                class="mb-8"
                color="blue"
                size="large"
                variant="tonal"
                :disabled="form.processing"
                :loading="form.processing">
                登録
            </v-btn>

            <v-card-text class="text-center px-0">
                <Link
                    class="text-primary"
                    :href="route('login')">
                    アカウントをお持ちの方はログインしてください。
                    <v-icon icon="mdi-chevron-right"></v-icon>
                </Link>
            </v-card-text>
        </form>
    </AuthCard>
</template>

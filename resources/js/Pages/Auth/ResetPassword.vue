<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthCard from '@/Components/Auth/AuthCard.vue'

const props = defineProps<{
    email: string
    token: string
}>()

defineOptions({ layout: GuestLayout })

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        },
    })
}
</script>

<template>
    <Head title="パスワードリセット" />

    <AuthCard card-title="パスワードリセット">
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

            <div class="flex items-center justify-end mt-4">
                <v-btn
                    type="submit"
                    block
                    class="mb-8"
                    color="blue"
                    size="large"
                    variant="tonal"
                    :disabled="form.processing"
                    :loading="form.processing">
                    パスワードリセット
                </v-btn>
            </div>
        </v-form>
    </AuthCard>
</template>

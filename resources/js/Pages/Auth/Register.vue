<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthCard from '@/Components/Auth/AuthCard.vue'
import ImageUploadField from '@/Components/Common/ImageUploadField.vue'
import DatePickerDialog from '@/Components/Common/DatePickerDialog.vue'
import type { RegisterPageResult } from '@/Types/dto.generated'

defineOptions({ layout: GuestLayout })

const props = defineProps<RegisterPageResult>()

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    birthday: null as string | null,
    avatar_image: null as File | null,
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
    <Head title="Register"/>

    <AuthCard :card-title="`${props.familyName} への参加登録`">
        <form @submit.prevent="submit">
            <div class="d-flex justify-center mb-4">
                <ImageUploadField v-model="form.avatar_image"/>
            </div>

            <div>
                <v-text-field
                    v-model="form.name"
                    label="名前"
                    :error="form.errors.hasOwnProperty('name')"
                    :error-messages="form.errors.name || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-account-outline"/>
            </div>

            <div class="mt-4">
                <v-text-field
                    v-model="form.email"
                    label="メールアドレス"
                    type="email"
                    :error="form.errors.hasOwnProperty('email')"
                    :error-messages="form.errors.email || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-email-outline"/>
            </div>

            <div class="mt-4">
                <v-text-field
                    v-model="form.password"
                    label="パスワード"
                    type="password"
                    :error="form.errors.hasOwnProperty('password')"
                    :error-messages="form.errors.password || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock-outline"/>
            </div>

            <div class="mt-4">
                <v-text-field
                    v-model="form.password_confirmation"
                    label="パスワード（確認）"
                    type="password"
                    :error="form.errors.hasOwnProperty('password_confirmation')"
                    :error-messages="form.errors.password_confirmation || []"
                    density="compact"
                    variant="outlined"
                    prepend-inner-icon="mdi-lock-outline"/>
            </div>

            <div class="mt-4">
                <DatePickerDialog
                    v-model="form.birthday"
                    label="生年月日（任意）"
                    placeholder="例: 2000年1月1日"
                    clearable
                    :error-messages="form.errors.birthday"
                    hint="生年月日を選択してください"/>
            </div>

            <v-btn
                type="submit"
                block
                class="mt-6 mb-8"
                color="primary"
                size="large"
                variant="flat"
                :disabled="form.processing"
                :loading="form.processing">
                登録して参加する
            </v-btn>

            <v-card-text class="text-center px-0">
                <Link
                    class="text-primary"
                    :href="route('login')">
                    アカウントをお持ちの方はログインしてください。
                    <v-icon icon="mdi-chevron-right"/>
                </Link>
            </v-card-text>
        </form>
    </AuthCard>
</template>

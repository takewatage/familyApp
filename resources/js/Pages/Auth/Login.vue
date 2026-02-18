<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthCard from '@/Components/Auth/AuthCard.vue'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'

defineProps<{
    canResetPassword?: boolean
    status?: string
}>()

defineOptions({ layout: GuestLayout })

const visible = ref(false)
const page = usePage()

const appName: string = page.props.appName
const form = useInertiaForm({
    email: '',
    password: '',
    familyCode: '',
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password')
        },
        onError: (errors) => {
            form.clearErrors()
            form.setError(errors)
        },
    })
}
</script>

<template>
    <Head title="Login" />

    <div class="login-page">
        <!-- 浮遊するデコレーション -->
        <div class="floating-decoration">👨‍👩‍👧‍👦</div>
        <div class="floating-decoration">💰</div>
        <div class="floating-decoration">🏠</div>
        <div class="floating-decoration">❤️</div>
        <div class="floating-decoration">✨</div>
        <div class="floating-decoration">🌟</div>
        <div class="floating-decoration">💕</div>
        <div class="floating-decoration">🎉</div>

        <!-- 背景の丸 -->
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>
        <div class="bg-circle"></div>

        <div class="login-container">
            <!-- ロゴエリア -->
            <div class="logo-area fade-in">
                <div class="logo-icon">
                    <span class="logo-emoji">🏠</span>
                </div>
                <h1 class="app-title">家族アプリ</h1>
                <p class="app-subtitle">家族をもっと楽しく、便利に ✨</p>
            </div>

            <AuthCard :title="appName">
                <div class="welcome-text">
                    <p class="welcome-subtitle">ログインして家族とつながろう</p>
                </div>
                <form @submit.prevent="submit">
                    <InputError
                        class="mt-2 mb-4"
                        :message="form.errors.email || form.errors.password || form.errors.familyCode" />

                    <v-text-field
                        v-model="form.email"
                        color="primary"
                        label="アカウント"
                        :error="form.errors.hasOwnProperty('email')"
                        density="comfortable"
                        placeholder="メールアドレス"
                        prepend-inner-icon="mdi-email-outline"
                        variant="outlined"></v-text-field>

                    <v-text-field
                        v-model="form.familyCode"
                        color="primary"
                        density="comfortable"
                        prepend-inner-icon="mdi-home-outline"
                        variant="outlined"
                        label="家族コード"
                        :error="form.errors.hasOwnProperty('familyCode')"
                        @click:append-inner="visible = !visible"></v-text-field>

                    <v-text-field
                        v-model="form.password"
                        color="primary"
                        :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
                        :type="visible ? 'text' : 'password'"
                        density="comfortable"
                        placeholder="Enter your password"
                        prepend-inner-icon="mdi-lock-outline"
                        variant="outlined"
                        label="パスワードを入力"
                        :error="form.errors.hasOwnProperty('email')"
                        @click:append-inner="visible = !visible"></v-text-field>
                    <v-btn
                        type="submit"
                        color="primary"
                        size="x-large"
                        block
                        class="login-btn"
                        :disabled="form.processing"
                        :loading="form.processing">
                        <v-icon start>mdi-login</v-icon>
                        ログイン
                    </v-btn>

                    <!--            <v-card-text class="text-center">-->
                    <!--                <Link-->
                    <!--                    class="text-primary"-->
                    <!--                    :href="route('register')">-->
                    <!--                    アカウントをお持ちでない場合は登録-->
                    <!--                    <v-icon icon="mdi-chevron-right"></v-icon>-->
                    <!--                </Link>-->
                    <!--            </v-card-text>-->
                </form>
            </AuthCard>

            <!-- フッター -->
            <div class="footer-text">
                <p>
                    <a href="#">利用規約</a>
                    ・
                    <a href="#">プライバシーポリシー</a>
                </p>
                <p style="margin-top: 8px; opacity: 0.7">© 2026 家族アプリ 👨‍👩‍👧‍👦</p>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
@use '/resources/sass/variables.module';

.test {
    font-size: variables.$font-small;
}
</style>

<style scoped lang="scss">
.login-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    position: relative;
    overflow: hidden;
}

/* 浮遊するデコレーション */
.floating-decoration {
    position: absolute;
    font-size: 40px;
    opacity: 0.6;
    animation: float 6s ease-in-out infinite;
    pointer-events: none;
}

.floating-decoration:nth-child(1) {
    top: 5%;
    left: 10%;
    animation-delay: 0s;
}

.floating-decoration:nth-child(2) {
    top: 15%;
    right: 15%;
    animation-delay: 1s;
    font-size: 35px;
}

.floating-decoration:nth-child(3) {
    top: 60%;
    left: 5%;
    animation-delay: 2s;
    font-size: 30px;
}

.floating-decoration:nth-child(4) {
    top: 75%;
    right: 10%;
    animation-delay: 3s;
    font-size: 45px;
}

.floating-decoration:nth-child(5) {
    top: 40%;
    left: 3%;
    animation-delay: 4s;
    font-size: 25px;
}

.floating-decoration:nth-child(6) {
    top: 25%;
    right: 5%;
    animation-delay: 5s;
    font-size: 28px;
}

.floating-decoration:nth-child(7) {
    bottom: 15%;
    left: 15%;
    animation-delay: 1.5s;
    font-size: 32px;
}

.floating-decoration:nth-child(8) {
    bottom: 25%;
    right: 20%;
    animation-delay: 2.5s;
    font-size: 38px;
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(10deg);
    }
}

/* 背景の丸い図形 */
.bg-circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    pointer-events: none;
}

.bg-circle:nth-child(9) {
    width: 300px;
    height: 300px;
    top: -100px;
    left: -100px;
}

.bg-circle:nth-child(10) {
    width: 200px;
    height: 200px;
    bottom: -50px;
    right: -50px;
}

.bg-circle:nth-child(11) {
    width: 150px;
    height: 150px;
    top: 50%;
    left: -75px;
}

.login-container {
    position: relative;
    z-index: 10;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.logo-area {
    text-align: center;
    margin-bottom: 24px;
}

.logo-icon {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.logo-emoji {
    font-size: 50px;
}

.app-title {
    color: white;
    font-size: 28px;
    font-weight: 800;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    margin-bottom: 4px;
}

.app-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 14px;
    font-weight: 600;
}

.login-card {
    width: 100%;
    max-width: 400px;
    border-radius: 24px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
    overflow: visible !important;
}

.login-card-content {
    padding: 32px 24px !important;
}

.welcome-text {
    text-align: center;
    margin-bottom: 24px;
}

.welcome-emoji {
    font-size: 48px;
    margin-bottom: 8px;
    display: block;
}

.welcome-title {
    font-size: 22px;
    font-weight: 700;
    color: #333;
    margin-bottom: 4px;
}

.welcome-subtitle {
    font-size: 14px;
    color: #888;
}

.form-field {
    margin-bottom: 16px;
}

.field-label {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-weight: 600;
    color: #555;
    font-size: 14px;
}

.field-label .emoji {
    margin-right: 8px;
    font-size: 18px;
}

.family-code-hint {
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
    border-radius: 12px;
    padding: 12px;
    margin-top: 8px;
    border: 1px solid #ffe082;
}

.hint-icon {
    font-size: 24px;
    margin-right: 10px;
}

.hint-text {
    font-size: 12px;
    color: #856404;
    line-height: 1.4;
}

.login-btn {
    border-radius: 16px !important;
    font-weight: 700 !important;
    font-size: 16px !important;
    letter-spacing: 0.5px;
    text-transform: none !important;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4) !important;
    transition: all 0.3s ease !important;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(102, 126, 234, 0.5) !important;
}

.divider-text span {
    padding: 0 12px;
}

.bottom-links {
    text-align: center;
    margin-top: 20px;
}

.bottom-links a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.bottom-links a:hover {
    text-decoration: underline;
}

.footer-text {
    text-align: center;
    margin-top: 24px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 13px;
}

.footer-text a {
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.password-toggle {
    cursor: pointer;
}

/* アニメーション */
.fade-in {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-card {
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

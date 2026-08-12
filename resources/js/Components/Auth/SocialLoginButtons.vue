<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string
        dividerText?: string
    }>(),
    {
        label: 'Googleでログイン',
        dividerText: 'または',
    },
)
</script>

<template>
    <div class="social-login">
        <div class="social-divider">
            <span>{{ dividerText }}</span>
        </div>

        <v-btn
            :href="route('auth.google.redirect')"
            variant="outlined"
            size="large"
            block
            class="google-btn">
            <v-icon start>mdi-google</v-icon>
            {{ label }}
        </v-btn>
    </div>
</template>

<style scoped lang="scss">
.social-login {
    margin-top: 20px;
}

.social-divider {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
    color: #999;
    font-size: 12px;

    &::before,
    &::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e0e0e0;
    }

    span {
        padding: 0 12px;
    }
}

.google-btn {
    border-radius: 16px !important;
    font-weight: 700 !important;
    text-transform: none !important;
    letter-spacing: 0.5px;
}
</style>

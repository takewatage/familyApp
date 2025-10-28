<script setup lang="ts">
const props = defineProps<{
    title?: string
    cardTitle?: string
}>()
</script>

<template>
    <div class="w-100 h-100 d-flex align-center justify-center flex-column">
        <h1
            v-if="props.title"
            class="text-center mb-5">
            {{ title }}
        </h1>

        <v-card
            class="auth-card mx-auto pa-12 pb-8"
            elevation="8"
            rounded="lg">
            <h1
                v-if="props.cardTitle"
                class="mb-6">
                {{ props.cardTitle }}
            </h1>
            <slot />
        </v-card>
    </div>
</template>

<style lang="scss">
.auth-card {
    max-width: 600px;
    width: 100%;

    form {
        max-width: 330px;
        margin: auto;
    }
}
</style>

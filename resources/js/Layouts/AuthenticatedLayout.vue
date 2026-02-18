<script setup lang="ts">
import AppHeader from '@/Components/Layout/AppHeader.vue'
import LoadingOverlay from '@/Components/App/LoadingOverlay.vue'
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <v-app>
        <Head title="HOME">
            <meta
                name="description"
                content="FamilyApp" />
        </Head>

        <AppHeader />

        <LoadingOverlay />

        <v-main>
            <Transition
                name="page"
                mode="out-in"
                appear>
                <div :key="$page.url">
                    <slot />
                </div>
            </Transition>
        </v-main>
    </v-app>
</template>

<style lang="scss" scoped>
.page-enter-active,
.page-leave-active {
    transition: all 0.5s ease-out;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>

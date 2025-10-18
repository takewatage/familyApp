<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <v-app id="guestLayout">
        <Head title="KoDo" />

        <Transition
            name="page"
            mode="out-in"
            appear>
            <v-main :key="$page.url">
                <slot />
            </v-main>
        </Transition>
    </v-app>
</template>

<style>
.page-enter-active,
.page-leave-active {
    transition: all 0.5s ease-out;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>

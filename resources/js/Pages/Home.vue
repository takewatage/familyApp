<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: AuthenticatedLayout })

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
    <p class="text-h1">You're logged in!</p>

    <v-btn @click="logout">logout</v-btn>
</template>

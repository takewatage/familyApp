<script setup>
import { Head } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
defineOptions({ layout: GuestLayout })

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
})
</script>

<template>
    <Head title="Welcome" />
    <div>
        <v-btn color="primary">test</v-btn>
        <p class="text-body-1">laravelVersion: {{ laravelVersion }}</p>
        <p class="text-body-1">phpVersion: {{ phpVersion }}</p>
    </div>
</template>

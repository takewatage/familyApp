<script setup lang="ts">
defineProps<{
    message?: string
}>()
</script>

<template>
    <div v-show="message">
        <p class="text-red-darken-2">
            {{ message }}
        </p>
    </div>
</template>

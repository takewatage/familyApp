<script setup lang="ts">
import { useLoading } from '@/Composables/Common/useLoading'

const { isLoading } = useLoading()
</script>

<template>
    <v-overlay
        :model-value="isLoading"
        persistent
        class="align-center justify-center"
        :z-index="9999"
        scrim="black"
        :opacity="0.45">
        <div class="d-flex flex-column align-center ga-3">
            <v-progress-circular
                indeterminate
                color="primary"
                :size="52"
                :width="4" />
            <span class="text-white text-body-2 font-weight-medium">Loading...</span>
        </div>
    </v-overlay>
</template>

<script setup lang="ts">
import { useSnackbar } from '@/Composables/Common/useSnackbar'

const { show, message, color } = useSnackbar()
</script>

<template>
    <v-snackbar
        v-model="show"
        :color="color"
        location="bottom"
        variant="tonal"
        :timeout="4000">
        {{ message }}
        <template #actions>
            <v-btn
                icon="mdi-close"
                @click="show = false" />
        </template>
    </v-snackbar>
</template>

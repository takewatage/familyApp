<template>
    <v-card>
        <v-card-title>{{ title }}</v-card-title>
        <v-card-text style="white-space: pre-line">{{ message }}</v-card-text>
        <v-card-actions>
            <v-spacer />
            <v-btn
                variant="text"
                @click="handleCancel">
                {{ cancelText }}
            </v-btn>
            <v-btn
                :color="confirmColor"
                variant="flat"
                @click="handleConfirm">
                {{ confirmText }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup lang="ts">
import { DialogComponentProps } from '@/Composables/Common/useDialogService'

interface Props extends DialogComponentProps<boolean> {
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    confirmColor?: string
    persistent?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Confirm',
    message: 'Are you sure?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    confirmColor: 'primary',
    persistent: false,
})

const handleConfirm = () => {
    props.onClose(true)
}

const handleCancel = () => {
    props.onClose(false)
}
</script>

<template>
    <v-card>
        <v-card-title>{{ title }}</v-card-title>
        <v-card-text>{{ message }}</v-card-text>
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
import { DialogComponentProps } from '@/composables/common/useDialogService'

interface Props extends DialogComponentProps<boolean> {
    title?: string
    message?: string
    confirmText?: string
    cancelText?: string
    confirmColor?: string
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Confirm',
    message: 'Are you sure?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    confirmColor: 'primary',
})

const handleConfirm = () => {
    props.onClose(true)
}

const handleCancel = () => {
    props.onClose(false)
}
</script>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'

defineOptions({ layout: AuthenticatedLayout })
// const dialog = useDialogService()
// const overlay = useOverlayService()
const { confirm } = useConfirmDialog()

const logout = () => {
    router.post(route('logout'))
}

const handle = async () => {
    const confirmed = await confirm({
        title: 'Delete Item',
        message: 'This action cannot be undone. Are you sure?',
        confirmText: 'Delete',
        confirmColor: 'error',
    })

    if (confirmed) {
        console.log('Item deleted')
    }
}
</script>

<template>
    <p class="text-h1">You're logged in!</p>

    <v-btn @click="logout">logout</v-btn>

    <v-btn @click="handle">confirm dialog</v-btn>
</template>

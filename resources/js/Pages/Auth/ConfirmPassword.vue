<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: GuestLayout })

// const form = useForm({
//     password: '',
// })

// const submit = () => {
//     form.post(route('password.confirm'), {
//         onFinish: () => {
//             form.reset()
//         },
//     })
// }
</script>

<template>
    <Head title="Confirm Password" />

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        This is a secure area of the application. Please confirm your password before continuing.
    </div>
</template>

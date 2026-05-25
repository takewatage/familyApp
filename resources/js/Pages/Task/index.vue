<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { TaskPageData } from '@/Types/dto.generated'
import FamilyTask from '@/Components/FamilyTask/FamilyTask.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<TaskPageData>()
</script>

<template>
    <FamilyTask :data="props" />
</template>

<style lang="scss" scoped>
.main-content {
    transition: height 0.2s ease-in;
    position: relative;
}
</style>

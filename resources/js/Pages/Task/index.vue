<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { TaskPageData } from '@/Types/dto.generated'
import FamilyTask from '@/Components/FamilyTask/FamilyTask.vue'

defineOptions({ layout: DokLayout })

const props = usePageProps<TaskPageData>()
</script>

<template>
    <FamilyTask
        :data="props"
        :key="props.categoryId" />
</template>

<style lang="scss" scoped>
.main-content {
    transition: height 0.2s ease-in;
    position: relative;
}
</style>

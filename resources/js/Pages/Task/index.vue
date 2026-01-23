<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { computed } from 'vue'
import { useLayout } from 'vuetify'
import Dok from '@/Pages/Dok/dok.vue'
import { usePageProps } from '@/Composables/Common/usePageProps';
import { DokResultData } from "../../../types/dto.generated";
import FamilyTask from "@/Components/Dok/FamilyTask/FamilyTask.vue";

defineOptions({ layout: DokLayout })

const { getLayoutItem } = useLayout()
const tabHeight = computed((): number => {
    const appBarSize = getLayoutItem('app-bar')?.size || 0
    return window.innerHeight - appBarSize
})

const props = usePageProps<DokResultData>();

console.log(props)

</script>

<template>
    <FamilyTask :categories="props.categories" :tasks="props.tasks"/>
</template>

<style lang="scss" scoped>
.main-content {
    transition: height 0.2s ease-in;
    position: relative;
}
</style>

<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useTab } from '@/Composables/Dok/useTab'
import Dok from '@/Pages/Dok/dok.vue'

defineOptions({ layout: AuthenticatedLayout })

const { tab } = useTab()
</script>

<template>
    <div>
        <v-tabs-window v-model="tab">
            <v-tabs-window-item
                class="main-content"
                value="dok">
                <Dok />
            </v-tabs-window-item>
            <v-tabs-window-item value="todo"></v-tabs-window-item>
        </v-tabs-window>
    </div>
</template>

<style lang="scss" scoped>
.main-content {
    height: calc(100vh - var(--v-layout-top) - var(--v-layout-bottom));
    position: relative;
}
</style>

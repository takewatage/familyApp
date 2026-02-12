<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { computed } from 'vue'
import { useLayout } from 'vuetify'
import { useTab } from '@/Composables/Dok/useTab'
import Dok from '@/Pages/Dok/dok.vue'

defineOptions({ layout: DokLayout })

const { getLayoutItem } = useLayout()
const tabHeight = computed((): number => {
    const appBarSize = getLayoutItem('app-bar')?.size || 0
    return window.innerHeight - appBarSize
})
const { tab } = useTab()

</script>

<template>
    <div>
        <v-tabs-window v-model="tab">
            <v-tabs-window-item
                class="main-content"
                value="dok"
                :style="{ height: tabHeight + 'px' }">
                <Dok/>
            </v-tabs-window-item>
            <v-tabs-window-item value="todo">
            </v-tabs-window-item>
        </v-tabs-window>
    </div>
</template>

<style lang="scss" scoped>
.main-content {
    transition: height 0.2s ease-in;
    position: relative;
}
</style>

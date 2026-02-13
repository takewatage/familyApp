<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useTab } from '@/Composables/Dok/useTab'

const drawer = ref(false)
const { tab } = useTab()
</script>

<template>
    <v-app>
        <Head title="どっちがお得カネ">
            <meta
                name="description"
                content="どっちがお得カネ" />
        </Head>

        <v-app-bar
            name="app-bar"
            density="compact">
            <template #prepend>
                <v-app-bar-nav-icon
                    variant="text"
                    @click.stop="drawer = !drawer" />
            </template>
            <!--            <v-tabs-->
            <!--                v-model="tab"-->
            <!--                grow-->
            <!--                color="primary">-->
            <!--                <v-tab value="dok">-->
            <!--                    <v-icon icon="mdi-currency-usd"></v-icon>-->
            <!--                </v-tab>-->

            <!--                <v-tab value="todo">-->
            <!--                    <v-icon icon="mdi-format-list-checks"></v-icon>-->
            <!--                </v-tab>-->
            <!--            </v-tabs>-->
            <template #append>
                <v-btn icon="mdi-dots-vertical" />
            </template>
        </v-app-bar>

        <v-navigation-drawer
            v-model="drawer"
            temporary>
            <v-list :items="['test']"></v-list>
        </v-navigation-drawer>

        <v-main>
            <Transition
                name="page"
                mode="out-in"
                appear>
                <div :key="$page.url">
                    <slot />
                </div>
            </Transition>
        </v-main>
    </v-app>
</template>

<style lang="scss" scoped>
.page-enter-active,
.page-leave-active {
    transition: all 0.5s ease-out;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'instant' })
}
import { mainAppMenuItems } from '@/Constants/mainAppMenu'
import LoadingOverlay from '@/Components/App/LoadingOverlay.vue'
import SnackbarNotification from '@/Components/App/SnackbarNotification.vue'
import { useAppTheme } from '@/Composables/Common/useAppTheme'

useAppTheme()

const drawer = ref(false)
const handleMenuClick = (route: string) => {
    router.visit(route)
    drawer.value = false
}
</script>

<template>
    <v-app>
        <Head title="どっちがお得カネ">
            <meta
                name="description"
                content="どっちがお得カネ"/>
        </Head>

        <LoadingOverlay/>
        <SnackbarNotification/>

        <v-app-bar
            name="app-bar"
            density="compact">
            <template #prepend>
                <v-app-bar-nav-icon
                    variant="text"
                    @click.stop="drawer = !drawer"/>
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
                <v-btn icon="mdi-dots-vertical"/>
            </template>
        </v-app-bar>

        <v-navigation-drawer
            v-model="drawer"
            temporary>
            <v-list>
                <v-list-item
                    v-for="item in mainAppMenuItems"
                    :key="item.route"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    @click="handleMenuClick(item.route)"/>
            </v-list>
        </v-navigation-drawer>

        <v-main>
            <Transition
                name="page"
                mode="out-in"
                appear
                @enter="scrollToTop">
                <div :key="$page.url">
                    <slot/>
                </div>
            </Transition>
        </v-main>
    </v-app>
</template>

<style lang="scss" scoped>
.page-enter-active,
.page-leave-active {
    transition: all 0.3s ease;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>

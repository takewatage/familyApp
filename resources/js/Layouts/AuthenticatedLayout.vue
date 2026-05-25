<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import { useAppTheme } from '@/Composables/Common/useAppTheme'
import LoadingOverlay from '@/Components/App/LoadingOverlay.vue'
import SnackbarNotification from '@/Components/App/SnackbarNotification.vue'
import InviteBottomSheet from '@/Components/Family/InviteBottomSheet.vue'
import { mainAppMenuItems } from '@/Constants/mainAppMenu'
import { FOOTER_APPS, DEFAULT_FOOTER_ITEMS, FOOTER_HIDDEN_ROUTES } from '@/Constants/footerApps'
import type { UserData } from '@/Types/dto.generated'

useAppTheme()

const page = usePage()

const user = computed(() => (page.props.auth as { user: UserData }).user)
const currentFamily = computed(() => page.props.currentFamily as { id: string; name: string } | null)
const inviteUrls = computed(() => (page.props.inviteUrls as Record<string, string>) ?? {})

const appMenuItems = mainAppMenuItems.filter((item) => item.route !== '/mypage')

const drawer = ref(false)
const inviteSheetVisible = ref(false)

const windowWidth = ref(window.innerWidth)
const drawerWidth = computed(() => Math.round(windowWidth.value * 0.9))

const onResize = () => {
    windowWidth.value = window.innerWidth
}
onMounted(() => window.addEventListener('resize', onResize))
onUnmounted(() => window.removeEventListener('resize', onResize))

const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'instant' })

const handleMenuClick = (routePath: string) => {
    router.visit(routePath)
    drawer.value = false
}

const footerItemKeys = computed<string[]>(() => {
    const items = (page.props.userSettings as any)?.footerItems
    return Array.isArray(items) ? items : DEFAULT_FOOTER_ITEMS
})

const resolvedFooterItems = computed(() =>
    footerItemKeys.value
        .map((key) => FOOTER_APPS.find((app) => app.key === key))
        .filter((app): app is NonNullable<typeof app> => app !== undefined),
)

const activeFooterKey = computed(() => {
    const url = page.url
    return FOOTER_APPS.find((app) => url.startsWith(app.route))?.key ?? null
})

const showFooter = computed(() =>
    !FOOTER_HIDDEN_ROUTES.some((route) => page.url.startsWith(route)),
)
</script>

<template>
    <v-app>
        <LoadingOverlay/>
        <SnackbarNotification/>
        <v-navigation-drawer
            v-model="drawer"
            temporary
            elevation="5"
            floating
            :width="drawerWidth">
            <v-list>
                <v-list-item
                    prepend-icon="mdi-account-group"
                    :title="currentFamily?.name ?? ''"
                    class="py-4"/>

                <v-divider/>

                <v-list-item
                    prepend-icon="mdi-account-plus"
                    title="メンバーを招待する"
                    @click="inviteSheetVisible = true; drawer = false"/>

                <v-divider/>

                <v-list-item
                    v-for="item in appMenuItems"
                    :key="item.route"
                    :prepend-icon="item.icon"
                    :title="item.title"
                    @click="handleMenuClick(item.route)"/>
            </v-list>
        </v-navigation-drawer>

        <v-app-bar
            name="app-bar"
            density="compact">
            <template #prepend>
                <v-app-bar-nav-icon @click.stop="drawer = !drawer"/>
            </template>
            <template #append>
                <v-btn
                    icon
                    @click="router.visit(route('mypage.index'))">
                    <v-avatar
                        size="32"
                        color="primary">
                        <v-img
                            v-if="user?.avatar"
                            :src="user.avatar.url"/>
                        <v-icon
                            v-else
                            icon="mdi-account"
                            color="white"/>
                    </v-avatar>
                </v-btn>
            </template>
        </v-app-bar>

        <InviteBottomSheet
            v-model="inviteSheetVisible"
            :family-name="currentFamily?.name ?? ''"
            :invite-urls="inviteUrls"/>

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

        <v-bottom-navigation
            v-if="showFooter"
            :model-value="activeFooterKey"
            color="primary"
            elevation="8"
            grow>
            <v-btn
                v-for="app in resolvedFooterItems"
                :key="app.key"
                :value="app.key"
                @click="router.visit(app.route)">
                <v-icon>{{ app.icon }}</v-icon>
                <span class="footer-label">{{ app.shortTitle ?? app.title }}</span>
            </v-btn>
        </v-bottom-navigation>
    </v-app>
</template>

<style lang="scss" scoped>
.footer-label {
    font-size: 10px;
}

.page-enter-active,
.page-leave-active {
    transition: all 0.3s ease;
}

.page-enter-from,
.page-leave-to {
    opacity: 0;
}
</style>

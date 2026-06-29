<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { useNavigation } from '@/Composables/Common/useNavigation'
import { computed } from 'vue'
import { useTheme } from 'vuetify'
import { FOOTER_APPS } from '@/Constants/footerApps'
import type { HomeResult, FamilyMemberData, VirtualUserData } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<HomeResult>()

const { navigateToDok, navigateTo } = useNavigation()

const vuetifyTheme = useTheme()
const heroGradient = computed(() => {
    const colors = vuetifyTheme.current.value.colors
    return `linear-gradient(135deg, ${colors.primary} 0%, ${colors.secondary} 100%)`
})

const page = usePage()
const currentFamily = computed(() => page.props.currentFamily as { id: string; name: string } | null)

const todayLabel = computed(() => {
    const d = new Date()
    const days = ['日', '月', '火', '水', '木', '金', '土']
    return `${d.getMonth() + 1}月${d.getDate()}日（${days[d.getDay()]}）`
})

type AvatarMember = {
    id: string
    name: string
    avatarUrl: string | null
}

const allMembers = computed<AvatarMember[]>(() => {
    const real = props.members.map((m: FamilyMemberData) => ({
        id: m.id,
        name: m.name,
        avatarUrl: m.avatar?.url ?? null,
    }))
    const virtual = props.virtualUsers.map((v: VirtualUserData) => ({
        id: v.id,
        name: v.name,
        avatarUrl: v.avatar?.url ?? null,
    }))
    return [...real, ...virtual]
})

const APP_ACTIONS: Record<string, () => void> = {
    dok: navigateToDok,
    tasks: () => navigateTo('tasks'),
}

const apps = FOOTER_APPS.filter((app) => app.key !== 'home' && APP_ACTIONS[app.key]).map((app) => ({
    title: app.title,
    icon: app.icon,
    action: APP_ACTIONS[app.key],
}))
</script>

<template>
    <div>
        <!-- ヒーローセクション -->
        <div class="home-hero" :style="{ background: heroGradient }">
            <div class="home-hero__content">
                <p class="home-hero__date">{{ todayLabel }}</p>
                <h1
                    v-if="currentFamily"
                    class="home-hero__family-name">
                    {{ currentFamily.name }}
                </h1>
                <div
                    v-if="allMembers.length > 0"
                    class="home-hero__members">
                    <v-avatar
                        v-for="member in allMembers"
                        :key="member.id"
                        size="40"
                        class="home-hero__avatar">
                        <v-img
                            v-if="member.avatarUrl"
                            :src="member.avatarUrl"
                            :alt="member.name"/>
                        <span
                            v-else
                            class="home-hero__avatar-initial">
                            {{ member.name.charAt(0) }}
                        </span>
                    </v-avatar>
                </div>
            </div>
        </div>

        <!-- アプリグリッドセクション -->
        <v-container class="pt-4">
            <p class="text-subtitle-2 font-weight-bold mb-3">アプリ</p>
            <div class="apps-grid">
                <div
                    v-for="app in apps"
                    :key="app.title"
                    v-ripple
                    class="app-item"
                    @click="app.action">
                    <v-icon
                        class="app-item__icon"
                        color="primary">
                        {{ app.icon }}
                    </v-icon>
                    <span class="app-item__title">{{ app.title }}</span>
                </div>
            </div>
        </v-container>
    </div>
</template>

<style scoped>
.home-hero {
    color: white;
    padding: 20px 16px 24px;
}

.home-hero__content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.home-hero__date {
    font-size: 13px;
    opacity: 0.85;
    margin: 0;
}

.home-hero__family-name {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 12px;
}

.home-hero__members {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.home-hero__avatar {
    border: 2px solid rgba(255, 255, 255, 0.8);
}

.home-hero__avatar-initial {
    font-size: 14px;
    font-weight: 600;
    color: white;
}

.apps-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.app-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 8px;
    border-radius: 12px;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
}

.app-item:hover {
    background: rgba(0, 0, 0, 0.05);
}

.app-item__icon {
    font-size: 32px;
    margin-bottom: 6px;
}

.app-item__title {
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    color: rgba(0, 0, 0, 0.7);
    word-break: keep-all;
}
</style>

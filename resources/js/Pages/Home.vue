<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { router } from '@inertiajs/vue3'
import { useNavigation } from '@/Composables/Common/useNavigation'

defineOptions({ layout: AuthenticatedLayout })

const { navigateToDok, navigateTo } = useNavigation()

const logout = () => {
    router.post(route('logout'))
}

const pages = [
    {
        title: 'どっちがお得カネ?',
        description: '価格比較ツール',
        icon: 'mdi-calculator',
        color: 'primary',
        action: navigateToDok,
    },
    {
        title: 'TODO',
        description: 'タスク管理',
        icon: 'mdi-checkbox-marked-circle',
        color: 'success',
        action: () => navigateTo('tasks'),
    },
]
</script>

<template>
    <v-container>
        <v-row class="mb-4">
            <v-col
                cols="12"
                class="d-flex justify-space-between align-center">
                <h1 class="text-h3">ホーム</h1>
                <v-btn
                    color="error"
                    variant="outlined"
                    @click="logout">
                    ログアウト
                </v-btn>
            </v-col>
        </v-row>

        <v-row>
            <v-col
                v-for="page in pages"
                :key="page.title"
                cols="12"
                sm="6">
                <v-card
                    class="pa-4"
                    hover
                    @click="page.action"
                    style="cursor: pointer">
                    <v-card-title class="d-flex align-center">
                        <v-icon
                            :color="page.color"
                            size="large"
                            class="mr-2">
                            {{ page.icon }}
                        </v-icon>
                        {{ page.title }}
                    </v-card-title>
                    <v-card-text>
                        {{ page.description }}
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup lang="ts">
import { usePwaUpdate } from '@/Composables/Common/usePwaUpdate'

const { needRefresh, update, dismiss } = usePwaUpdate()
</script>

<template>
    <v-snackbar
        v-model="needRefresh"
        :timeout="-1"
        location="bottom"
        class="pwa-update-notifier">
        新しいバージョンがあります
        <template #actions>
            <v-btn
                variant="text"
                @click="dismiss">
                あとで
            </v-btn>
            <v-btn
                color="primary"
                variant="flat"
                @click="update">
                更新
            </v-btn>
        </template>
    </v-snackbar>
</template>

<style scoped lang="scss">
// フッターナビと重ならないよう持ち上げる
.pwa-update-notifier {
    margin-bottom: calc(72px + env(safe-area-inset-bottom));
}
</style>

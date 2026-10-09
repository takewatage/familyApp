<script setup lang="ts">
// 予定の参加者アイコンを重ねて表示する。max（既定 4 人）を超えた分は「+N」にまとめる。

import { computed } from 'vue'
import type { CalendarParticipant } from '@/Types/calendar'

const props = withDefaults(
    defineProps<{
        participants: CalendarParticipant[]
        /** 表示するアイコンの最大数 */
        max?: number
        size?: number
    }>(),
    { max: 4, size: 24 },
)

const visible = computed(() => props.participants.slice(0, props.max))
const rest = computed(() => props.participants.length - visible.value.length)
const label = computed(() => props.participants.map((p) => p.name).join('、'))
</script>

<template>
    <div
        v-if="participants.length"
        class="participant-avatars"
        role="img"
        :aria-label="`参加者: ${label}`">
        <v-avatar
            v-for="p in visible"
            :key="p.id"
            :size="size"
            color="secondary"
            class="participant-avatars__item">
            <v-img
                v-if="p.avatarUrl"
                :src="p.avatarUrl"
                :alt="p.name" />
            <span
                v-else
                class="participant-avatars__initial">
                {{ p.name.charAt(0) }}
            </span>
        </v-avatar>
        <v-avatar
            v-if="rest > 0"
            :size="size"
            color="grey-lighten-1"
            class="participant-avatars__item">
            <span class="participant-avatars__initial">+{{ rest }}</span>
        </v-avatar>
    </div>
</template>

<style scoped>
.participant-avatars {
    display: inline-flex;
    align-items: center;
}

/* 少しずつ重ねて表示する */
.participant-avatars__item + .participant-avatars__item {
    margin-left: -6px;
}

.participant-avatars__item {
    border: 2px solid rgb(var(--v-theme-surface));
}

.participant-avatars__initial {
    font-size: 11px;
    font-weight: 600;
    color: white;
}
</style>

<script setup lang="ts">
// 予定のメモ（予定一覧の時刻の下に表示する）。長いメモは 2 行で切り、改行はそのまま表示する。

defineProps<{
    memo: string
}>()
</script>

<template>
    <div class="event-memo">
        <v-icon
            icon="mdi-note-text-outline"
            size="14"
            class="event-memo__icon" />
        <span class="event-memo__text">{{ memo }}</span>
    </div>
</template>

<style scoped>
.event-memo {
    display: flex;
    align-items: flex-start;
    gap: 4px;
    margin-top: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(var(--v-theme-on-surface), 0.04);
    color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity));
    font-size: 12px;
    line-height: 1.5;
}

.event-memo__icon {
    flex-shrink: 0;
    margin-top: 2px;
}

/* 改行を活かしつつ 2 行まで（はみ出しは「…」） */
.event-memo__text {
    white-space: pre-line;
    overflow-wrap: anywhere;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
}
</style>

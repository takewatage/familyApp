<script setup lang="ts">
// 入力欄（outlined の v-text-field）と同じ見た目で表示する選択欄。DatePickerDialog と同じく読み取り専用の
// v-text-field で描くため、枠線の色・高さ・ラベルが他の入力欄とそろう。
// タップ（Enter / Space）で click を emit し、利用側がボトムシート等を開く。左端の装飾（色・アバター等）は prepend slot。

defineProps<{
    label: string
    /** 表示する値（未選択なら placeholder を出す） */
    value: string
    placeholder?: string
    /** 読み上げ用のラベル（未指定なら label） */
    ariaLabel?: string
}>()

const emit = defineEmits<{
    (e: 'click'): void
}>()
</script>

<template>
    <!-- 中の入力欄はフォーカスさせず、外側をボタンとして扱う -->
    <div
        class="picker-field"
        role="button"
        tabindex="0"
        :aria-label="ariaLabel ?? label"
        @click="emit('click')"
        @keydown.enter.prevent="emit('click')"
        @keydown.space.prevent="emit('click')">
        <v-text-field
            :model-value="value"
            :label="label"
            :placeholder="placeholder"
            persistent-placeholder
            variant="outlined"
            density="comfortable"
            readonly
            tabindex="-1"
            hide-details
            append-inner-icon="mdi-chevron-right">
            <template
                v-if="$slots.prepend"
                #prepend-inner>
                <slot name="prepend" />
            </template>
        </v-text-field>
    </div>
</template>

<style scoped>
.picker-field,
.picker-field :deep(input) {
    cursor: pointer;
}

.picker-field :deep(input) {
    text-overflow: ellipsis;
}
</style>

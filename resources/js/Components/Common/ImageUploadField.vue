<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
    modelValue?: File | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void
}>()

const vFileInputRef = ref<InstanceType<typeof import('vuetify/components').VFileInput> | null>(null)
const previewUrl = ref<string | null>(null)

watch(
    () => props.modelValue,
    (file) => {
        if (file) {
            previewUrl.value = URL.createObjectURL(file)
        } else {
            previewUrl.value = null
        }
    },
)

const triggerFileInput = () => {
    const input = (vFileInputRef.value as any)?.$el?.querySelector('input[type="file"]')
    input?.click()
}

const onFileChange = (files: File | File[] | null) => {
    const file = Array.isArray(files) ? (files[0] ?? null) : files
    emit('update:modelValue', file)
    if (file) {
        previewUrl.value = URL.createObjectURL(file)
    } else {
        previewUrl.value = null
    }
}

const clearImage = () => {
    emit('update:modelValue', null)
    previewUrl.value = null
}
</script>

<template>
    <!-- 非表示のv-file-input（実際のファイル選択UI） -->
    <v-file-input
        ref="vFileInputRef"
        accept="image/*"
        class="d-none"
        @update:model-value="onFileChange" />

    <!-- 画像未選択：アップロードエリア -->
    <div
        v-if="!previewUrl"
        class="image-upload-area"
        v-ripple="{ class: `text-primary` }"
        @click="triggerFileInput">
        <v-icon
            size="20"
            color="grey-lighten-1">
            mdi-plus
        </v-icon>
    </div>

    <!-- 画像選択済み：プレビュー表示 -->
    <div
        v-else
        class="image-preview-area">
        <img
            :src="previewUrl"
            class="preview-img"
            alt="プレビュー" />
        <v-btn
            icon
            size="small"
            class="remove-btn"
            color="error"
            elevation="2"
            @click="clearImage">
            <v-icon size="small">mdi-close</v-icon>
        </v-btn>
    </div>
</template>

<style scoped lang="scss">
.image-upload-area {
    width: fit-content;
    border: 1px solid #ddd;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.image-preview-area {
    position: relative;
    display: inline-block;
    width: 100%;
}

.preview-img {
    width: 100%;
    max-height: 240px;
    object-fit: cover;
    border-radius: 12px;
    display: block;
}

.remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
}
</style>

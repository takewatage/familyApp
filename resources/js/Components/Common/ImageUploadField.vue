<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
    modelValue?: File | null
    currentUrl?: string | null
    previewVariant?: 'default' | 'avatar'
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: File | null): void
    (e: 'clear'): void
}>()

const vFileInputRef = ref<InstanceType<typeof import('vuetify/components').VFileInput> | null>(null)
const previewUrl = ref<string | null>(props.currentUrl ?? null)

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
    emit('clear')
    previewUrl.value = null
}
</script>

<template>
    <!-- 非表示のv-file-input（実際のファイル選択UI） -->
    <v-file-input
        ref="vFileInputRef"
        accept="image/*"
        class="d-none"
        @update:model-value="onFileChange"/>

    <!-- avatar variant -->
    <template v-if="previewVariant === 'avatar'">
        <div class="avatar-wrapper">
            <v-avatar
                size="100"
                :color="previewUrl ? undefined : 'grey-lighten-3'"
                class="avatar-clickable"
                @click="triggerFileInput">
                <v-img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt="プレビュー"/>
                <v-icon
                    v-else
                    size="60"
                    color="grey-lighten-1">
                    mdi-account
                </v-icon>
            </v-avatar>
            <v-btn
                v-if="previewUrl"
                icon
                size="x-small"
                class="avatar-remove-btn"
                color="error"
                elevation="2"
                @click.stop="clearImage">
                <v-icon size="x-small">mdi-close</v-icon>
            </v-btn>
            <v-btn
                icon
                size="x-small"
                class="avatar-edit-btn"
                color="primary"
                elevation="2"
                @click="triggerFileInput">
                <v-icon size="x-small">mdi-pencil</v-icon>
            </v-btn>
        </div>
    </template>

    <!-- default variant -->
    <template v-else>
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
                alt="プレビュー"/>
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

.avatar-wrapper {
    position: relative;
    display: inline-block;
}

.avatar-clickable {
    cursor: pointer;
}

.avatar-remove-btn {
    position: absolute;
    top: -4px;
    right: -4px;
}

.avatar-edit-btn {
    position: absolute;
    bottom: -4px;
    right: -4px;
}
</style>

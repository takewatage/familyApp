<script setup lang="ts">
// 家族設定の画像（バナー・家族のアイコン）の設定カード。変更・削除はオーナーのみ。
// 画像を選ぶとプレビューし、「保存」で updateRoute に送る（フィールド名は field）。

import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'

const props = defineProps<{
    /** カードの見出し（例: ホームのバナー） */
    title: string
    /** 画像の説明・推奨サイズ */
    hint: string
    /** プレビューの形（banner: 3:1 の横長 / icon: 丸） */
    shape: 'banner' | 'icon'
    imageUrl?: string | null
    isOwner: boolean
    /** 送信するフィールド名（banner / icon） */
    field: string
    updateRoute: string
    destroyRoute: string
}>()

const snackbar = useSnackbar()
const { confirm } = useConfirmDialog()

const form = useInertiaForm<Record<string, File | null>>({
    [props.field]: null,
})

const fileInput = ref<HTMLInputElement | null>(null)
const selectedUrl = ref<string | null>(null)
const selectedFile = computed(() => form[props.field] as File | null)

watch(selectedFile, (file) => {
    if (selectedUrl.value) {
        URL.revokeObjectURL(selectedUrl.value)
    }

    selectedUrl.value = file ? URL.createObjectURL(file) : null
})

onBeforeUnmount(() => {
    if (selectedUrl.value) {
        URL.revokeObjectURL(selectedUrl.value)
    }
})

/** 選んだ画像（未保存）を優先してプレビューする */
const previewUrl = computed(() => selectedUrl.value ?? props.imageUrl ?? null)
const fieldError = computed(() => (form.errors as Record<string, string | undefined>)[props.field])

function pickFile(): void {
    fileInput.value?.click()
}

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement

    form[props.field] = input.files?.[0] ?? null
    // 同じ画像を選び直しても change が発生するよう値をクリアする
    input.value = ''
}

function clearSelection(): void {
    form[props.field] = null
}

function handleSubmit(): void {
    form.post(route(props.updateRoute), {
        preserveScroll: true,
        onSuccess: () => {
            form[props.field] = null
            snackbar.success(`${props.title}を更新しました`)
        },
        onError: () => snackbar.error('更新に失敗しました'),
    })
}

async function removeImage(): Promise<void> {
    const ok = await confirm({
        title: `${props.title}を削除しますか？`,
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route(props.destroyRoute), {
        preserveScroll: true,
        onSuccess: () => snackbar.success(`${props.title}を削除しました`),
        onError: () => snackbar.error('削除に失敗しました'),
    })
}
</script>

<template>
    <v-card>
        <v-card-title>{{ title }}</v-card-title>
        <v-card-text>
            <div
                class="image-preview"
                :class="[
                    `image-preview--${shape}`,
                    { 'image-preview--empty': !previewUrl, 'image-preview--clickable': isOwner },
                ]"
                :role="isOwner ? 'button' : undefined"
                :aria-label="isOwner ? `${title}の画像を選択` : undefined"
                @click="isOwner && pickFile()">
                <v-img
                    v-if="previewUrl"
                    :src="previewUrl"
                    cover
                    :aspect-ratio="shape === 'banner' ? 3 : 1"
                    :alt="`${title}のプレビュー`"/>
                <div
                    v-else
                    class="image-preview__placeholder text-medium-emphasis">
                    <v-icon
                        :icon="shape === 'banner' ? 'mdi-image-outline' : 'mdi-account-group'"
                        size="32"/>
                    <span
                        v-if="shape === 'banner'"
                        class="text-caption">
                        {{ isOwner ? 'タップして画像を選択' : '設定されていません' }}
                    </span>
                </div>
            </div>

            <input
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="d-none"
                @change="onFileChange">

            <div
                v-if="fieldError"
                class="text-error text-caption mt-1">
                {{ fieldError }}
            </div>

            <p class="text-caption text-medium-emphasis mt-2 mb-0">{{ hint }}</p>
        </v-card-text>

        <v-card-actions v-if="isOwner">
            <v-btn
                v-if="imageUrl && !selectedFile"
                variant="text"
                color="error"
                prepend-icon="mdi-delete-outline"
                @click="removeImage">
                削除
            </v-btn>
            <v-btn
                v-if="selectedFile"
                variant="text"
                @click="clearSelection">
                取り消す
            </v-btn>
            <v-spacer/>
            <v-btn
                variant="text"
                prepend-icon="mdi-image-edit-outline"
                @click="pickFile">
                画像を選択
            </v-btn>
            <v-btn
                color="primary"
                variant="flat"
                :disabled="!selectedFile"
                :loading="form.processing"
                @click="handleSubmit">
                保存
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped>
.image-preview {
    overflow: hidden;
}

.image-preview--banner {
    border-radius: 8px;
}

/* アイコンは丸く、中央に表示する */
.image-preview--icon {
    width: 96px;
    height: 96px;
    margin: 0 auto;
    border-radius: 50%;
}

.image-preview--clickable {
    cursor: pointer;
}

.image-preview--empty {
    border: 2px dashed rgba(var(--v-theme-on-surface), 0.2);
}

.image-preview--banner.image-preview--empty {
    aspect-ratio: 3;
}

.image-preview__placeholder {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
</style>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import ImageUploadField from '@/Components/Common/ImageUploadField.vue'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import type { FamilyPwaSettingsData, UpdateFamilyPwaSettingsRequest } from '@/Types/dto.generated'

const RECOMMENDED_MIN_SIZE = 512

const props = defineProps<{
    pwa: FamilyPwaSettingsData
    isOwner: boolean
}>()

const snackbar = useSnackbar()
const { confirm } = useConfirmDialog()

const form = useInertiaForm<UpdateFamilyPwaSettingsRequest & { icon: File | null }>({
    name: props.pwa.name,
    icon: null,
})

const selectedIconUrl = ref<string | null>(null)
const isSmallImage = ref(false)

watch(
    () => form.icon,
    (file) => {
        if (selectedIconUrl.value) {
            URL.revokeObjectURL(selectedIconUrl.value)
        }

        selectedIconUrl.value = file ? URL.createObjectURL(file) : null
        isSmallImage.value = false

        if (!file || !selectedIconUrl.value) {
            return
        }

        const img = new Image()
        img.onload = () => {
            isSmallImage.value = img.naturalWidth < RECOMMENDED_MIN_SIZE || img.naturalHeight < RECOMMENDED_MIN_SIZE
        }
        img.src = selectedIconUrl.value
    },
)

const previewIconUrl = computed(() => selectedIconUrl.value ?? props.pwa.iconApple)

function handleSubmit() {
    form.post(route('family.settings.pwa.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.icon = null
            snackbar.success('アプリ設定を更新しました')
        },
        onError: () => snackbar.error('更新に失敗しました'),
    })
}

async function resetIcon() {
    const ok = await confirm({
        title: 'アプリ画像を削除しますか？',
        message: '既定のアイコンに戻ります。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('family.settings.pwa.icon.destroy'), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('アプリ画像を削除しました'),
        onError: () => snackbar.error('削除に失敗しました'),
    })
}
</script>

<template>
    <v-card>
        <v-card-title>アプリ設定（ホーム画面）</v-card-title>
        <v-card-text>
            <div class="d-flex justify-center mb-4">
                <div class="home-preview">
                    <img
                        :src="previewIconUrl"
                        class="home-preview__icon"
                        alt="アプリ画像のプレビュー"/>
                    <div class="home-preview__label text-caption">{{ form.name || ' ' }}</div>
                </div>
            </div>

            <v-text-field
                v-model="form.name"
                label="アプリ名"
                prepend-inner-icon="mdi-cellphone"
                variant="outlined"
                density="comfortable"
                counter="30"
                maxlength="30"
                hint="ホーム画面では長い名前は省略されます（目安 12 文字）"
                persistent-hint
                :readonly="!props.isOwner"
                :error-messages="form.errors.name"/>

            <template v-if="props.isOwner">
                <div class="text-body-2 mt-4 mb-2">アプリ画像（正方形・512×512 以上推奨）</div>
                <div class="d-flex align-center ga-4">
                    <ImageUploadField v-model="form.icon"/>
                    <v-btn
                        v-if="props.pwa.isCustomIcon && !form.icon"
                        variant="text"
                        color="error"
                        prepend-icon="mdi-restore"
                        @click="resetIcon">
                        既定のアイコンに戻す
                    </v-btn>
                </div>
                <div
                    v-if="form.errors.icon"
                    class="text-error text-caption mt-1">
                    {{ form.errors.icon }}
                </div>
                <v-alert
                    v-if="isSmallImage"
                    type="warning"
                    variant="tonal"
                    density="compact"
                    class="mt-2">
                    画像が 512×512 より小さいため、端末によっては粗く表示されます。
                </v-alert>
            </template>

            <v-alert
                type="info"
                variant="tonal"
                density="compact"
                class="mt-4 text-caption">
                インストール済みのアプリへの反映は Android で最大 1 日程度かかります。iOS はホーム画面から削除して追加し直してください。PC Chrome はアイコンの変更が反映されません。
            </v-alert>
        </v-card-text>
        <v-card-actions v-if="props.isOwner">
            <v-spacer/>
            <v-btn
                color="primary"
                variant="flat"
                :loading="form.processing"
                @click="handleSubmit">
                保存
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped lang="scss">
.home-preview {
    width: 88px;
    text-align: center;
}

.home-preview__icon {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

.home-preview__label {
    margin-top: 4px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>

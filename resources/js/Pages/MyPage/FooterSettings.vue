<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { VueDraggable } from 'vue-draggable-plus'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { FOOTER_APPS, REQUIRED_FOOTER_ITEMS } from '@/Constants/footerApps'
import { usePageProps } from '@/Composables/Common/usePageProps'
import type { FooterSettingsResult } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<FooterSettingsResult>()

const snackbar = useSnackbar()

const form = useInertiaForm({
    footerItems: [...props.value.footerItems],
})

const resolvedFooterItems = computed(() =>
    form.footerItems
        .map((key) => FOOTER_APPS.find((app) => app.key === key))
        .filter((app): app is NonNullable<typeof app> => app !== undefined),
)

const availableApps = computed(() =>
    FOOTER_APPS.filter((app) => !form.footerItems.includes(app.key)),
)

function removeFooterItem(key: string) {
    if (REQUIRED_FOOTER_ITEMS.includes(key)) {
        return
    }

    form.footerItems = form.footerItems.filter((k) => k !== key)
}

function addFooterItem(key: string) {
    if (!form.footerItems.includes(key)) {
        form.footerItems = [...form.footerItems, key]
    }
}

function handleSubmit() {
    form.post(route('mypage.footer-settings.update'), {
        onSuccess: () => snackbar.success('フッター設定を保存しました'),
        onError: () => snackbar.error('フッター設定の保存に失敗しました'),
    })
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="8"
                md="6">
                <div class="d-flex align-center mb-4">
                    <v-btn
                        icon="mdi-arrow-left"
                        variant="text"
                        @click="router.visit(route('mypage.index'))"/>
                    <span class="text-h6 ml-2">アプリショートカット設定</span>
                </div>

                <p class="text-body-2 text-medium-emphasis mb-4">
                    フッターに表示するアプリを選択・並び替えできます。
                </p>

                <!-- フッター項目リスト -->
                <VueDraggable
                    v-model="form.footerItems"
                    :animation="200"
                    handle=".drag-handle">
                    <div
                        v-for="app in resolvedFooterItems"
                        :key="app.key"
                        class="mb-2">
                        <v-card>
                            <div class="d-flex align-center px-3 py-4">
                                <v-icon
                                    class="drag-handle mr-3"
                                    color="grey-lighten-1"
                                    size="20">
                                    mdi-drag
                                </v-icon>
                                <v-icon
                                    class="mr-3"
                                    size="20">
                                    {{ app.icon }}
                                </v-icon>
                                <span class="text-body-1 flex-grow-1">{{ app.title }}</span>
                                <v-chip
                                    v-if="REQUIRED_FOOTER_ITEMS.includes(app.key)"
                                    size="x-small"
                                    class="mr-1">
                                    固定
                                </v-chip>
                                <v-btn
                                    v-else
                                    icon="mdi-close"
                                    size="x-small"
                                    variant="text"
                                    color="error"
                                    @click="removeFooterItem(app.key)"/>
                            </div>
                        </v-card>
                    </div>
                </VueDraggable>

                <!-- 追加できるアプリ -->
                <div
                    v-if="availableApps.length > 0"
                    class="mt-4">
                    <p class="text-body-2 text-medium-emphasis mb-3">追加できるアプリ</p>
                    <div
                        v-for="app in availableApps"
                        :key="app.key"
                        class="mb-2">
                        <v-card
                            variant="outlined"
                            @click="addFooterItem(app.key)">
                            <div class="d-flex align-center px-3 py-4">
                                <v-icon
                                    class="mr-3"
                                    size="20"
                                    color="medium-emphasis">
                                    {{ app.icon }}
                                </v-icon>
                                <span class="text-body-1 flex-grow-1 text-medium-emphasis">{{ app.title }}</span>
                                <v-icon color="primary">mdi-plus</v-icon>
                            </div>
                        </v-card>
                    </div>
                </div>

                <!-- 保存ボタン -->
                <div class="mt-6">
                    <v-btn
                        color="primary"
                        variant="flat"
                        block
                        :loading="form.processing"
                        @click="handleSubmit">
                        保存
                    </v-btn>
                </div>
            </v-col>
        </v-row>
    </v-container>
</template>

<style scoped lang="scss">
.drag-handle {
    cursor: grab;

    &:active {
        cursor: grabbing;
    }
}
</style>

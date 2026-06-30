<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { VueDraggable } from 'vue-draggable-plus'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { budgetCategoryApi } from '@/Api/budgetCategoryApi'
import type { CategoriesPageResult, CategoryData, StoreCategoryRequest } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<CategoriesPageResult>()
const { confirm } = useConfirmDialog()
const snackbar = useSnackbar()

// プリセットカラー（色選択の主導線。HEX 手入力も可）
const presetColors = [
    '#EF4444', '#F97316', '#F59E0B', '#10B981', '#14B8A6',
    '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899', '#6B7280',
]

const systemCategories = computed(() => props.value.categories.filter((c) => c.isSystem))
const familyCategories = computed(() => props.value.categories.filter((c) => !c.isSystem))

// 最上位の家族カテゴリー（並び替え対象）
const familyTops = computed(() => familyCategories.value.filter((c) => !c.parentId))

const childrenOf = (id: string) => familyCategories.value.filter((c) => c.parentId === id)

// ドラッグ用のローカル並び（トップレベルのみ並び替え対象）
const draggableTops = ref<CategoryData[]>(familyTops.value)

watch(familyTops, (tops) => {
    draggableTops.value = [...tops]
})

async function onDragEnd() {
    const changed = draggableTops.value.some((c, i) => c.sortOrder !== i + 1)

    if (!changed) {
        return
    }

    try {
        await budgetCategoryApi.reorder({
            categories: draggableTops.value.map((c, i) => ({ id: c.id, sort: i + 1 })),
        })
        router.reload({ only: ['categories'] })
    } catch {
        snackbar.error('並び替えに失敗しました')
        router.reload({ only: ['categories'] })
    }
}

// ----- 登録 / 編集ダイアログ -----
const dialog = ref(false)
const editing = ref<CategoryData | null>(null)
const formKey = ref(0)

const dialogTitle = computed(() => (editing.value ? 'カテゴリーを編集' : 'カテゴリーを追加'))

// 親候補（最上位の家族カテゴリーのみ。編集中は自分自身を除外）
const parentOptions = computed(() =>
    familyCategories.value
        .filter((c) => !c.parentId && c.id !== editing.value?.id)
        .map((c) => ({ value: c.id, title: c.name })),
)

// 子を持つカテゴリーは別カテゴリーの子にできない（親子 2 階層制限）。編集時は親選択を無効化する。
const editingHasChildren = computed(
    () => !!editing.value && familyCategories.value.some((c) => c.parentId === editing.value?.id),
)

const form = useInertiaForm<StoreCategoryRequest>({
    name: '',
    color: presetColors[0],
    icon: undefined,
    parentId: undefined,
})

function openCreate(parentId?: string) {
    editing.value = null
    form.reset()
    form.color = presetColors[0]
    form.parentId = parentId
    formKey.value++
    dialog.value = true
}

function openEdit(category: CategoryData) {
    editing.value = category
    form.name = category.name
    form.color = category.color
    form.icon = category.icon ?? undefined
    form.parentId = category.parentId ?? undefined
    formKey.value++
    dialog.value = true
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success(editing.value ? 'カテゴリーを更新しました' : 'カテゴリーを追加しました')
            dialog.value = false
        },
        onError: () => snackbar.error('保存に失敗しました'),
    }

    if (editing.value) {
        form.patch(route('budget.categories.update', editing.value.id), options)
    } else {
        form.post(route('budget.categories.store'), options)
    }
}

async function remove(category: CategoryData) {
    const ok = await confirm({
        title: 'カテゴリーを削除しますか？',
        message: '子カテゴリーも併せて無効化されます。過去の支出の記録は残ります。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('budget.categories.destroy', category.id), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('カテゴリーを削除しました'),
        onError: () => snackbar.error('削除に失敗しました'),
    })
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="10"
                md="8">
                <div class="d-flex align-center justify-space-between mb-4">
                    <h2 class="text-h6">カテゴリー管理</h2>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openCreate()">
                        追加
                    </v-btn>
                </div>

                <!-- 家族のカテゴリー（並び替え可） -->
                <v-card class="mb-4">
                    <v-card-title class="text-subtitle-1">わが家のカテゴリー</v-card-title>
                    <v-card-text
                        v-if="!familyTops.length"
                        class="text-medium-emphasis">
                        まだカテゴリーがありません。「追加」から作成してください。
                    </v-card-text>

                    <VueDraggable
                        v-else
                        v-model="draggableTops"
                        handle=".drag-handle"
                        :animation="150"
                        @end="onDragEnd">
                        <div
                            v-for="top in draggableTops"
                            :key="top.id">
                            <v-list-item>
                                <template #prepend>
                                    <v-icon
                                        class="drag-handle mr-2"
                                        icon="mdi-drag"
                                        style="cursor: grab"/>
                                    <v-avatar
                                        :color="top.color"
                                        size="32">
                                        <v-icon
                                            v-if="top.icon"
                                            :icon="`mdi-${top.icon}`"
                                            color="white"
                                            size="small"/>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="ml-2">{{ top.name }}</v-list-item-title>

                                <template #append>
                                    <v-btn
                                        icon="mdi-plus"
                                        variant="text"
                                        size="small"
                                        title="サブカテゴリー追加"
                                        @click="openCreate(top.id)"/>
                                    <v-btn
                                        icon="mdi-pencil"
                                        variant="text"
                                        size="small"
                                        @click="openEdit(top)"/>
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        variant="text"
                                        size="small"
                                        color="error"
                                        @click="remove(top)"/>
                                </template>
                            </v-list-item>

                            <!-- 子カテゴリー -->
                            <v-list-item
                                v-for="child in childrenOf(top.id)"
                                :key="child.id"
                                class="pl-12">
                                <template #prepend>
                                    <v-avatar
                                        :color="child.color"
                                        size="24">
                                        <v-icon
                                            v-if="child.icon"
                                            :icon="`mdi-${child.icon}`"
                                            color="white"
                                            size="x-small"/>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="ml-2 text-body-2">{{ child.name }}</v-list-item-title>

                                <template #append>
                                    <v-btn
                                        icon="mdi-pencil"
                                        variant="text"
                                        size="small"
                                        @click="openEdit(child)"/>
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        variant="text"
                                        size="small"
                                        color="error"
                                        @click="remove(child)"/>
                                </template>
                            </v-list-item>
                        </div>
                    </VueDraggable>
                </v-card>

                <!-- システム標準（読み取り専用） -->
                <v-card
                    v-if="systemCategories.length"
                    variant="tonal">
                    <v-card-title class="text-subtitle-1">システム標準（編集不可）</v-card-title>
                    <v-card-text class="d-flex flex-wrap ga-2">
                        <v-chip
                            v-for="c in systemCategories"
                            :key="c.id"
                            :prepend-icon="c.icon ? `mdi-${c.icon}` : undefined"
                            :color="c.color"
                            variant="flat"
                            size="small">
                            {{ c.name }}
                        </v-chip>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- 登録 / 編集ダイアログ -->
        <v-dialog
            v-model="dialog"
            max-width="500"
            persistent>
            <v-card :key="formKey">
                <v-card-title>{{ dialogTitle }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.name"
                            label="カテゴリー名"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.name"/>

                        <v-select
                            v-model="form.parentId"
                            :items="parentOptions"
                            label="親カテゴリー（任意）"
                            variant="outlined"
                            density="comfortable"
                            clearable
                            :disabled="editingHasChildren"
                            :hint="editingHasChildren ? '子カテゴリーを持つため親を変更できません' : undefined"
                            persistent-hint
                            :error-messages="form.errors.parentId"
                            class="mt-2"/>

                        <v-text-field
                            v-model="form.icon"
                            label="アイコン（mdi 名・任意）"
                            placeholder="例: food, cart, home"
                            variant="outlined"
                            density="comfortable"
                            :prepend-inner-icon="form.icon ? `mdi-${form.icon}` : 'mdi-shape-outline'"
                            :error-messages="form.errors.icon"
                            class="mt-2"/>

                        <div class="text-caption text-medium-emphasis mb-1 mt-2">色</div>
                        <div class="d-flex flex-wrap ga-2 mb-2">
                            <v-avatar
                                v-for="color in presetColors"
                                :key="color"
                                :color="color"
                                size="32"
                                style="cursor: pointer"
                                @click="form.color = color">
                                <v-icon
                                    v-if="form.color === color"
                                    icon="mdi-check"
                                    color="white"
                                    size="small"/>
                            </v-avatar>
                        </div>
                        <v-text-field
                            v-model="form.color"
                            label="色（HEX）"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.color"/>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn
                        variant="text"
                        @click="dialog = false">
                        キャンセル
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        :loading="form.processing"
                        @click="submit">
                        {{ editing ? '更新' : '追加' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

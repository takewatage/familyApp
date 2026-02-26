<script setup lang="ts">
import { ref } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { TaskCategoryData } from '@/Types/dto.generated'
import { DialogComponentProps } from '@/Composables/Common/useDialogService'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import axios from 'axios'

type Props = {
    categories: TaskCategoryData[]
    onCategoriesChange: (categories: TaskCategoryData[]) => void
} & DialogComponentProps<void>

const props = defineProps<Props>()

const localCategories = ref<TaskCategoryData[]>([...props.categories])
const editingId = ref<string | null>(null)
const editForm = ref({ name: '' })
const isAdding = ref(false)
const addForm = ref({ name: '' })
const isSubmitting = ref(false)

const notifyChange = () => {
    props.onCategoriesChange([...localCategories.value])
}

const onDragEnd = async () => {
    // 順番が変わったかチェック
    const hasChanged = localCategories.value.some((cat, index) => cat.sort !== index + 1)
    if (!hasChanged) return

    const orders = localCategories.value.map((cat, index) => ({
        id: cat.id,
        sort: index + 1,
    }))
    notifyChange()
    try {
        await axios.post('/task-categories/reorder', { orders })
    } catch (error) {
        console.error('Failed to reorder:', error)
    }
}

const startEdit = (category: TaskCategoryData) => {
    editingId.value = category.id
    editForm.value = { name: category.name }
    isAdding.value = false
}

const cancelEdit = () => {
    editingId.value = null
}

const saveEdit = async (category: TaskCategoryData) => {
    if (!editForm.value.name.trim()) return
    isSubmitting.value = true
    try {
        const response = await axios.patch(`/task-categories/${category.id}`, {
            name: editForm.value.name,
        })
        const index = localCategories.value.findIndex((c) => c.id === category.id)
        if (index !== -1) {
            localCategories.value[index] = response.data.category
        }
        editingId.value = null
        notifyChange()
    } catch (error) {
        console.error('Failed to update category:', error)
    } finally {
        isSubmitting.value = false
    }
}

const deleteCategory = async (category: TaskCategoryData) => {
    const { confirm } = useConfirmDialog()
    const result = await confirm({
        title: 'カテゴリーの削除',
        message: `「${category.name}」を削除しますか？\nこのカテゴリーに含まれるタスクも全て削除されます。`,
        confirmText: '削除',
        confirmColor: 'error',
    })
    if (!result) return

    const backup = [...localCategories.value]
    localCategories.value = localCategories.value.filter((c) => c.id !== category.id)
    notifyChange()

    try {
        await axios.delete(`/task-categories/${category.id}`)
    } catch (error) {
        localCategories.value = backup
        notifyChange()
        console.error('Failed to delete category:', error)
    }
}

const startAdd = () => {
    isAdding.value = true
    editingId.value = null
    addForm.value = { name: '' }
}

const cancelAdd = () => {
    isAdding.value = false
}

const saveAdd = async () => {
    if (!addForm.value.name.trim()) return
    isSubmitting.value = true
    try {
        const response = await axios.post('/task-categories', {
            name: addForm.value.name,
        })
        localCategories.value.push(response.data.category)
        isAdding.value = false
        notifyChange()
    } catch (error) {
        console.error('Failed to create category:', error)
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <div class="category-manage">
        <!-- スクロールエリア -->
        <div class="scroll-area pa-3">
            <VueDraggable
                v-model="localCategories"
                :animation="200"
                handle=".drag-handle"
                @end="onDragEnd">
                <div
                    v-for="category in localCategories"
                    :key="category.id"
                    class="mb-2">
                    <!-- 編集フォーム -->
                    <v-card
                        v-if="editingId === category.id"
                        elevation="3"
                        class="pa-3">
                        <v-text-field
                            v-model="editForm.name"
                            label="カテゴリー名"
                            variant="outlined"
                            density="compact"
                            autofocus
                            hide-details
                            class="mb-3" />
                        <div class="d-flex gap-2 justify-end">
                            <v-btn
                                size="small"
                                variant="text"
                                @click="cancelEdit">
                                キャンセル
                            </v-btn>
                            <v-btn
                                size="small"
                                color="primary"
                                variant="flat"
                                :loading="isSubmitting"
                                :disabled="!editForm.name.trim()"
                                @click="saveEdit(category)">
                                保存
                            </v-btn>
                        </div>
                    </v-card>

                    <!-- 通常カード -->
                    <v-card
                        v-else
                        class="category-card">
                        <div class="d-flex align-center px-3 py-4">
                            <v-icon
                                class="drag-handle mr-3"
                                color="grey-lighten-1"
                                size="20">
                                mdi-drag
                            </v-icon>
                            <span class="text-body-1 flex-grow-1">{{ category.name }}</span>
                            <v-btn
                                density="comfortable"
                                icon="mdi-pencil"
                                variant="text"
                                size="small"
                                @click="startEdit(category)" />
                            <v-btn
                                density="comfortable"
                                icon="mdi-delete-outline"
                                variant="text"
                                size="small"
                                color="error"
                                @click="deleteCategory(category)" />
                        </div>
                    </v-card>
                </div>
            </VueDraggable>

            <!-- 追加フォーム -->
            <v-card
                v-if="isAdding"
                variant="outlined"
                class="mt-2 pa-3">
                <div class="text-subtitle-2 mb-3 text-medium-emphasis">新しいカテゴリー</div>
                <v-text-field
                    v-model="addForm.name"
                    label="カテゴリー名"
                    variant="outlined"
                    density="compact"
                    autofocus
                    hide-details
                    class="mb-3" />
                <div class="d-flex gap-2 justify-end">
                    <v-btn
                        size="small"
                        variant="text"
                        @click="cancelAdd">
                        キャンセル
                    </v-btn>
                    <v-btn
                        size="small"
                        color="primary"
                        variant="flat"
                        :loading="isSubmitting"
                        :disabled="!addForm.name.trim()"
                        @click="saveAdd">
                        追加
                    </v-btn>
                </div>
            </v-card>
        </div>

        <!-- 固定フッター -->
        <div class="footer pa-3">
            <v-btn
                v-if="!isAdding"
                prepend-icon="mdi-plus"
                color="primary"
                variant="tonal"
                block
                @click="startAdd">
                カテゴリーを追加
            </v-btn>
        </div>
    </div>
</template>

<style scoped lang="scss">
.category-manage {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.scroll-area {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
}

.footer {
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    background-color: rgb(var(--v-theme-background));
}

.category-card {
    transition: box-shadow 0.2s;
}

.drag-handle {
    cursor: grab;

    &:active {
        cursor: grabbing;
    }
}
</style>

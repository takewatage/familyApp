<script setup lang="ts">
import { ref } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
    SortTaskCategoryRequest,
    TaskCategoryData,
} from '@/Types/dto.generated'
import {
    DialogComponentProps,
    useDialogService,
} from '@/Composables/Common/useDialogService'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { taskCategoryApi } from '@/Api/taskCategoryApi'
import CategoryEditDialog from '@/Components/FamilyTask/CategoryEditDialog.vue'

type Props = {
    categories: TaskCategoryData[]
    onCategoriesChange: (categories: TaskCategoryData[]) => void
} & DialogComponentProps<void>

const props = defineProps<Props>()

const localCategories = ref<TaskCategoryData[]>([...props.categories])
const isSheet = ref(false)
const sheetCategoryData = ref<null | TaskCategoryData>(null)

const { open } = useDialogService()

const notifyChange = () => {
    props.onCategoriesChange([...localCategories.value])
}

const onDragEnd = async () => {
    // 順番が変わったかチェック
    const hasChanged = localCategories.value.some(
        (cat, index) => cat.sort !== index + 1,
    )
    if (!hasChanged) return

    const params: SortTaskCategoryRequest = {
        categories: localCategories.value.map((cat, index) => ({
            id: cat.id,
            sort: index + 1,
        })),
    }
    notifyChange()
    try {
        await taskCategoryApi.reorder(params)
    } catch (error) {
        console.error('Failed to reorder:', error)
    }
}

const openEditDialog = (category?: TaskCategoryData) => {
    open({
        component: CategoryEditDialog,
        props: {
            category,
            onSaved: (saved: TaskCategoryData) => {
                if (category) {
                    const index = localCategories.value.findIndex(
                        (c) => c.id === saved.id,
                    )
                    if (index !== -1) {
                        localCategories.value[index] = saved
                    }
                } else {
                    localCategories.value.push(saved)
                }
                notifyChange()
            },
        },
        maxWidth: 400,
    })
}

const startEdit = () => {
    if (!sheetCategoryData.value) {
        return
    }
    const category = sheetCategoryData.value
    isSheet.value = false
    openEditDialog(category)
}

const deleteCategory = async () => {
    if (!sheetCategoryData.value) {
        return
    }

    const { confirm } = useConfirmDialog()
    const result = await confirm({
        title: 'カテゴリーの削除',
        message: `「${sheetCategoryData.value.name}」を削除しますか？\nこのカテゴリーに含まれるタスクも全て削除されます。`,
        confirmText: '削除',
        confirmColor: 'error',
    })
    if (!result) return

    const backup = [...localCategories.value]
    localCategories.value = localCategories.value.filter(
        (c) => c.id !== sheetCategoryData.value?.id,
    )
    notifyChange()

    try {
        await taskCategoryApi.destroy(sheetCategoryData.value.id)
        isSheet.value = false
    } catch (error) {
        localCategories.value = backup
        notifyChange()
        console.error('Failed to delete category:', error)
    }
}

const openSheet = (cateogry: TaskCategoryData) => {
    sheetCategoryData.value = cateogry
    isSheet.value = true
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
                    <v-card class="category-card">
                        <div class="d-flex align-center px-3 py-4">
                            <v-icon
                                class="drag-handle mr-3"
                                color="grey-lighten-1"
                                size="20">
                                mdi-drag
                            </v-icon>
                            <span class="text-body-1 flex-grow-1">
                                {{ category.name }}
                            </span>
                            <v-btn
                                density="comfortable"
                                icon="mdi-dots-horizontal"
                                variant="text"
                                @click="openSheet(category)"/>
                        </div>
                    </v-card>
                </div>
            </VueDraggable>
        </div>

        <!-- 固定フッター -->
        <div class="footer pa-3">
            <v-btn
                prepend-icon="mdi-plus"
                color="primary"
                variant="tonal"
                block
                @click="openEditDialog()">
                カテゴリーを追加
            </v-btn>
        </div>

        <v-bottom-sheet v-model="isSheet">
            <v-list>
                <v-list-subheader title="カテゴリー編集"></v-list-subheader>
                <v-list-item
                    v-ripple="{ class: `text-primary` }"
                    title="編集"
                    prepend-icon="mdi-pencil"
                    @click="startEdit()"/>
                <v-list-item
                    v-ripple="{ class: `text-primary` }"
                    prepend-icon="mdi-delete-outline"
                    title="削除"
                    @click="deleteCategory()"/>
            </v-list>
        </v-bottom-sheet>
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

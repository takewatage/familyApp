<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useTab } from '@/Composables/Dok/useTab'
import { TaskCategoryData, TaskData, TaskPageData } from '@/Types/dto.generated'
import axios from 'axios'
import SaveTaskForm from './SaveTaskForm.vue'
import CategoryManageDialog from './CategoryManageDialog.vue'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'

const props = defineProps<{
    data: TaskPageData
}>()

const selectedCategory = ref('')
const localTasks = ref<TaskData[]>(props.data.tasks || [])
const categories = ref<TaskCategoryData[]>(props.data.categories || [])
const settingsSelection = ref([])
const showCompletedTasks = ref(false)
const addTaskOpen = ref(false)

const { tab } = useTab()
const dialogService = useDialogService()

const unCompleteTasks = computed(() => {
    return localTasks.value.filter((x) => x.categoryId === selectedCategory.value && !x.isCompleted)
})

const completeTasks = computed(() => {
    return localTasks.value.filter((x) => x.categoryId === selectedCategory.value && x.isCompleted)
})

const nonCategories = computed(() => {
    return categories.value.length === 0
})

const init = () => {
    selectedCategory.value = props.data.categories.length ? props.data.categories[0].id : ''
}

const toggleTodo = async (task: TaskData) => {
    // 楽観的更新
    const index = localTasks.value.findIndex((t) => t.id === task.id)
    if (index !== -1) {
        localTasks.value[index].isCompleted = !localTasks.value[index].isCompleted
    }

    try {
        await axios.patch(`/tasks/${task.id}/toggle`)
    } catch (error) {
        // エラー時はロールバック
        if (index !== -1) {
            localTasks.value[index].isCompleted = !localTasks.value[index].isCompleted
        }
        console.error('Failed to toggle task:', error)
    }
}

const handleTaskCreated = (newTask: TaskData) => {
    // 重複チェックして追加
    if (!localTasks.value.find((t) => t.id === newTask.id)) {
        localTasks.value.push(newTask)
    }
    addTaskOpen.value = false
}

const handleTaskUpdated = (updatedTask: TaskData) => {
    const index = localTasks.value.findIndex((t) => t.id === updatedTask.id)
    if (index !== -1) {
        localTasks.value[index] = { ...updatedTask }
    }
}

const setupEchoListeners = () => {
    if (!props.data.familyId) return

    window.Echo.private(`family.${props.data.familyId}`).listen('.task.updated', (e: {
        task: TaskData;
        action: string
    }) => {
        const { task, action } = e
        let index

        switch (action) {
            case 'created':
                if (!localTasks.value.find((t) => t.id === task.id)) {
                    localTasks.value.push(task)
                }
                break

            case 'updated':
                index = localTasks.value.findIndex((t) => t.id === task.id)
                if (index !== -1) {
                    localTasks.value[index] = { ...task } // リアクティブ更新のためスプレッド
                }
                break

            case 'deleted':
                localTasks.value = localTasks.value.filter((t) => t.id !== task.id)
                break
        }
    })
}

const cleanupEchoListeners = () => {
    if (props.data.familyId) {
        window.Echo.leave(`family.${props.data.familyId}`)
    }
}

const onEdit = async (task: TaskData) => {
    const dialog = dialogService.open<TaskData>({
        component: SaveTaskForm,
        props: {
            categories: categories.value,
            selectedCategory: task.categoryId,
            editMode: true,
            task: task,
        },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: {
            title: 'タスク編集',
            actions: [
                {
                    text: '削除',
                    onClick: async () => {
                        const deleted = await onDelete(task)
                        if (deleted) {
                            dialog.close()
                        }
                    },
                },
            ],
        },
    })

    const result = await dialog.afterClosed()
    if (result) {
        handleTaskUpdated(result)
    }
}

const onCategoryEdit = () => {
    dialogService.open({
        component: CategoryManageDialog,
        props: {
            categories: categories.value,
            onCategoriesChange: (updated: TaskCategoryData[]) => {
                categories.value = updated
                if (!updated.find((c) => c.id === selectedCategory.value)) {
                    selectedCategory.value = updated.length ? updated[0].id : ''
                }
            },
        },
        fullscreen: true,
        transition: 'dialog-bottom-transition',
        toolbar: {
            title: 'カテゴリー管理',
        },
    })
}

const onDelete = async (task: TaskData): Promise<boolean> => {
    const { confirm } = useConfirmDialog()
    const result = await confirm({
        title: 'タスクの削除',
        message: `「${task.content}」を削除しますか？`,
        confirmText: '削除',
        confirmColor: 'error',
        persistent: true,
    })

    if (!result) {
        return false
    }

    // 楽観的更新
    const backup = [...localTasks.value]
    localTasks.value = localTasks.value.filter((t) => t.id !== task.id)

    try {
        await axios.delete(`/tasks/${task.id}`, { showLoading: true })
        return true
    } catch (error) {
        // エラー時はロールバック
        localTasks.value = backup
        console.error('Failed to delete task:', error)
        alert('タスクの削除に失敗しました')
        return false
    }
}

onMounted(() => {
    init()
    setupEchoListeners()
})

onUnmounted(() => {
    cleanupEchoListeners()
})

watch(
    () => tab.value,
    (tab) => {
        if (tab === 'todo') {
            init()
        }
    },
)
</script>

<template>
    <div class="todo-list">
        <v-chip-group
            v-model="selectedCategory"
            mandatory
            selected-class="text-primary"
            :mobile="true">
            <v-chip
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
                :color="category.color"
                size="large">
                {{ category.name }}
            </v-chip>
            <div
                v-ripple="{ class: `text-primary` }"
                class="category-edit-btn"
                @click="onCategoryEdit">
                <v-icon
                    size="20"
                    color="grey">
                    mdi-cog
                </v-icon>
            </div>
        </v-chip-group>
    </div>

    <div
        v-if="nonCategories"
        class="empty-state">
        <v-icon
            icon="mdi-shape-plus-outline"
            color="primary"/>
        <h3 class="text-h6 mb-2">カテゴリーがありません</h3>
        <p class="text-body-2">上の追加ボタンから追加しましょう</p>
    </div>

    <Transition
        v-if="!nonCategories"
        name="category-change"
        mode="out-in">
        <v-list
            :key="selectedCategory"
            v-model:selected="settingsSelection"
            lines="three"
            select-strategy="leaf"
            class="px-3 task-content"
            bg-color="transparent">
            <v-list-subheader>未完了タスク</v-list-subheader>

            <div
                v-if="unCompleteTasks.length === 0"
                class="empty-state">
                <v-icon
                    icon="mdi-coffee-to-go-outline"
                    color="primary"/>
                <h3 class="text-h6 mb-2">タスクがありません</h3>
                <p class="text-body-2">右下の＋ボタンから追加しましょう</p>
            </div>

            <TransitionGroup name="task-list">
                <v-list-item
                    v-for="item in unCompleteTasks"
                    :key="item.id"
                    v-ripple="{ class: `text-primary` }"
                    :title="item.content"
                    min-height="auto"
                    class="todo-list-item bg-surface rounded-lg pa-3 mb-3"
                    :class="{ 'line-through': item.isCompleted }"
                    elevation="4"
                    @click="toggleTodo(item)">
                    <template #prepend>
                        <v-list-item-action start>
                            <v-checkbox-btn
                                color="primary"
                                :model-value="item.isCompleted"/>
                        </v-list-item-action>
                    </template>
                    <template #append>
                        <div class="d-flex gap-1">
                            <v-btn
                                density="comfortable"
                                icon="mdi-pencil"
                                @click.stop="onEdit(item)"></v-btn>
                            <!--                            <v-btn-->
                            <!--                                density="comfortable"-->
                            <!--                                icon="mdi-delete"-->
                            <!--                                color="error"-->
                            <!--                                @click.stop="onDelete(item)"></v-btn>-->
                        </div>
                    </template>
                </v-list-item>
            </TransitionGroup>
            <v-list-subheader
                class="d-flex align-center cursor-pointer"
                @click="showCompletedTasks = !showCompletedTasks">
                <span>完了タスク ({{ completeTasks.length }})</span>
                <v-icon class="ml-1">
                    {{ showCompletedTasks ? 'mdi-chevron-up' : 'mdi-chevron-down' }}
                </v-icon>
            </v-list-subheader>

            <v-expand-transition>
                <div v-show="showCompletedTasks">
                    <TransitionGroup name="task-list">
                        <v-list-item
                            v-for="task in completeTasks"
                            :key="task.id"
                            v-ripple="{ class: `text-primary` }"
                            :title="task.content"
                            min-height="auto"
                            class="todo-list-item bg-surface rounded-lg pa-3 mb-3"
                            :class="{ 'line-through': task.isCompleted }"
                            @click="toggleTodo(task)">
                            <template #prepend>
                                <v-list-item-action start>
                                    <v-checkbox-btn
                                        color="primary"
                                        :model-value="task.isCompleted"/>
                                </v-list-item-action>
                            </template>
                            <!--                            <template #append>-->
                            <!--                                <v-btn-->
                            <!--                                    density="comfortable"-->
                            <!--                                    icon="mdi-delete"-->
                            <!--                                    color="error"-->
                            <!--                                    @click.stop="onDelete(task)"></v-btn>-->
                            <!--                            </template>-->
                        </v-list-item>
                    </TransitionGroup>
                </div>
            </v-expand-transition>
        </v-list>
    </Transition>

    <!-- タスク追加FABボタン -->
    <v-btn
        v-if="!nonCategories"
        class="add-task-fab"
        color="primary"
        icon="mdi-plus"
        size="large"
        @click="addTaskOpen = true"></v-btn>

    <!-- タスク追加フォーム -->
    <v-bottom-sheet v-model="addTaskOpen">
        <SaveTaskForm
            :categories="categories"
            :selected-category="selectedCategory"
            :on-close="() => (addTaskOpen = false)"
            @task-created="handleTaskCreated"/>
    </v-bottom-sheet>
</template>

<style lang="scss" scoped>
.add-task-fab {
    position: fixed;
    bottom: 80px;
    right: 16px;
    z-index: 100;
}

.todo-list-item {
    :deep(.v-list-item__prepend) {
        padding-top: 0 !important;
    }

    &.line-through {
        :deep(.v-list-item__content) {
            text-decoration: line-through;
        }
    }
}

:deep(.v-slide-group__container) {
    padding: 10px;
}

.task-content {
    position: relative;
    overflow-x: hidden;
}

// カテゴリー変更時のトランジション
.category-change-enter-active,
.category-change-leave-active {
    transition: opacity 0.3s ease;
}

.category-change-enter-from,
.category-change-leave-to {
    opacity: 0;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #888;

    .v-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }
}

// タスク個別のトランジションのスタイル
//.task-list-move,
//.task-list-enter-active,
//.task-list-leave-active {
//    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
//}
//
//.task-list-enter-from {
//    opacity: 0;
//    transform: translateX(-30px);
//}
//
//.task-list-leave-to {
//    opacity: 0;
//    transform: translateX(30px);
////}
//
//// ポイント：leave-active で position: absolute にして高さを確保
//.task-list-leave-active {
//    position: absolute;
//    left: 0;
//    right: 0;
//}

.task-list-container {
    position: relative;
    overflow: hidden; // はみ出し防止
}

// 移動アニメーション（残りのアイテムが滑らかに詰まる）
.task-list-move {
    transition: transform 0.4s ease;
}

// 入場アニメーション
.task-list-enter-active {
    transition: all 0.3s ease;
}

// 退場アニメーション
.task-list-leave-active {
    transition: all 0.3s ease;
    position: absolute;
    left: 12px;
    right: 12px;
}

// 入場開始状態
.task-list-enter-from {
    opacity: 0;
    transform: scale(0.9) translateX(-20px);
}

// 退場終了状態
.task-list-leave-to {
    opacity: 0;
    transform: scale(0.9) translateX(20px);
}

.category-edit-btn {
    margin: 4px 8px 4px 0;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
</style>

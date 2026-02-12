<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useTab } from '@/Composables/Dok/useTab'
import { useTaskForm } from '@/Composables/Dok/useTaskForm'
import { TaskCategoryData, TaskData, TaskPageData } from "@/Types/dto.generated";
import axios from 'axios'
import { router } from "@inertiajs/vue3";

const props = defineProps<{
    data: TaskPageData
}>()

const selectedCategory = ref(props.data.categoryId || '')
const localTasks = ref<TaskData[]>(props.data.tasks || [])
const categories = ref<TaskCategoryData[]>(props.data.categories || [])
const isLoading = ref(false)

const { tab } = useTab()
const { isOpen, isSubmitting, form, openSheet, closeSheet, submitTask } = useTaskForm()

const unCompleteTasks = computed(() => {
    return localTasks.value.filter((x) => x.categoryId === selectedCategory.value && !x.isCompleted)
})

const completeTasks = computed(() => {
    return localTasks.value.filter((x) => x.categoryId === selectedCategory.value && x.isCompleted)
})

const init = () => {
    selectedCategory.value = props.data.categories.length ? props.data.categories[0].id : ''
}

const settingsSelection = ref([])

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

const handleSubmitTask = async () => {
    const newTask = await submitTask()
    if (newTask) {
        console.log(newTask)
        console.log(localTasks)
        // 重複チェックして追加
        if (!localTasks.value.find(t => t.id === newTask.id)) {
            localTasks.value.push(newTask)
        }
    }
}

const setupEchoListeners = () => {
    if (!props.data.familyId) return

    window.Echo.private(`family.${props.data.familyId}`)
        .listen('.task.updated', (e: { task: TaskData; action: string }) => {
            const { task, action } = e

            switch (action) {
                case 'created':
                    if (!localTasks.value.find(t => t.id === task.id)) {
                        localTasks.value.push(task)
                    }
                    break

                case 'updated':
                    const index = localTasks.value.findIndex(t => t.id === task.id)
                    if (index !== -1) {
                        localTasks.value[index] = { ...task }  // リアクティブ更新のためスプレッド
                    }
                    break

                case 'deleted':
                    localTasks.value = localTasks.value.filter(t => t.id !== task.id)
                    break
            }
        })
}

const cleanupEchoListeners = () => {
    if (props.data.familyId) {
        window.Echo.leave(`family.${props.data.familyId}`)
    }
}

const onEdit = (task: TaskData) => {
    console.log(task)

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
    <div class="todo-list pa-3">
        <v-chip-group
            v-model="selectedCategory"
            mandatory
            selected-class="text-primary"
            :mobile="true">
            <v-chip
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
                :color="category.color">
                {{ category.name }}
            </v-chip>
        </v-chip-group>
    </div>

    <!-- ローディング表示 -->
    <v-progress-linear
        v-if="isLoading"
        indeterminate
        color="primary"
    />

    <Transition name="category-change" mode="out-in">
        <v-list
            :key="selectedCategory"
            v-model:selected="settingsSelection"
            lines="three"
            select-strategy="leaf"
            class="px-3 task-content"
            bg-color="transparent">
            <v-list-subheader>未完了タスク</v-list-subheader>
            <TransitionGroup name="task-list">
                <v-list-item
                    v-for="item in unCompleteTasks"
                    :key="item.id"
                    :title="item.content"
                    min-height="auto"
                    class="todo-list-item bg-surface rounded-lg pa-3 mb-3"
                    :class="{ 'line-through': item.isCompleted }"
                    @click="toggleTodo(item)">
                    <template #prepend>
                        <v-list-item-action start>
                            <v-checkbox-btn
                                color="secondary"
                                :model-value="item.isCompleted"/>
                        </v-list-item-action>
                    </template>
                    <template #append>
                        <v-btn
                            @click.stop="onEdit(item)"
                            density="comfortable"
                            icon="mdi-pencil"></v-btn>
                    </template>
                </v-list-item>
            </TransitionGroup>
            <!--            <v-expansion-panels>-->
            <!--                <v-expansion-panel-->
            <!--                    title="Title"-->
            <!--                    text="Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi, ratione debitis quis est labore voluptatibus! Eaque cupiditate minima"-->
            <!--                >-->
            <!--                </v-expansion-panel>-->
            <!--            </v-expansion-panels>-->
            <v-list-subheader>完了タスク</v-list-subheader>
            <TransitionGroup name="task-list">
                <v-list-item
                    v-for="task in completeTasks"
                    :key="task.id"
                    :title="task.content"
                    min-height="auto"
                    class="todo-list-item bg-surface rounded-lg pa-3 mb-3"
                    :class="{ 'line-through': task.isCompleted }"
                    @click="toggleTodo(task)">
                    <template #prepend>
                        <v-list-item-action start>
                            <v-checkbox-btn
                                color="secondary"
                                :model-value="task.isCompleted"/>
                        </v-list-item-action>
                    </template>
                </v-list-item>
            </TransitionGroup>
        </v-list>
    </Transition>

    <!-- タスク追加FABボタン -->
    <v-btn
        class="add-task-fab"
        color="primary"
        icon="mdi-plus"
        size="large"
        @click="openSheet(selectedCategory)">
    </v-btn>

    <!-- タスク追加用 Bottom Sheet -->
    <v-bottom-sheet v-model="isOpen">
        <v-card class="pa-4">
            <v-card-title class="text-h6">
                タスクを追加
            </v-card-title>
            <v-card-text class="px-0">
                <v-text-field
                    v-model="form.content"
                    label="タスク内容"
                    placeholder="やることを入力..."
                    variant="outlined"
                    autofocus
                    @keyup.enter="handleSubmitTask">
                </v-text-field>
                <v-select
                    v-model="form.categoryId"
                    :items="categories"
                    item-title="name"
                    item-value="id"
                    label="カテゴリー"
                    variant="outlined">
                </v-select>
            </v-card-text>
            <v-card-actions class="justify-end">
                <v-btn
                    variant="text"
                    @click="closeSheet">
                    キャンセル
                </v-btn>
                <v-btn
                    color="primary"
                    variant="flat"
                    :loading="isSubmitting"
                    :disabled="!form.content.trim() || !form.categoryId"
                    @click="handleSubmitTask">
                    追加
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style lang="scss" scoped>
.todo-list {
    overflow-x: hidden;
}

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

.task-content {
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

// タスク個別のトランジションのスタイル
.task-list-move {
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.task-list-enter-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.task-list-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.task-list-enter-from {
    opacity: 0;
    transform: translateX(30px) scale(0.95);
    max-height: 0;
}

.task-list-leave-to {
    opacity: 0;
    transform: translateX(-30px) scale(0.95);
    max-height: 0;
    margin-bottom: 0 !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
</style>


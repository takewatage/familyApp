<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useTab } from '@/Composables/Dok/useTab'
import { TaskCategoryData, TaskData } from "../../../../types/dto.generated";

const props = defineProps<{
    categories: TaskCategoryData[]
    tasks: TaskData[]
}>()

const selectedCategory = ref('')
const localTasks = ref<Task[]>(props.tasks || [])
const categories = ref<Category[]>(props.categories || [])
const { tab } = useTab()

const unCompleteTasks = computed(() => {
    return localTasks.value.filter((x) => x.categoryId === selectedCategory.value && !x.isCompleted)
})

const completeTasks = computed(() => {
    return localTasks.value.filter((x) => x.categoryId === selectedCategory.value && x.isCompleted)
})

const init = () => {
    selectedCategory.value = props.categories.length ? props.categories[0].id : ''
}

const settingsSelection = ref([])

const toggleTodo = (task: Task) => {
    console.log(task.id)
    // 楽観的更新
    const index = localTasks.value.findIndex((t) => t.id === task.id)
    if (index !== -1) {
        localTasks.value[index].isCompleted = !localTasks.value[index].isCompleted
    }

    // apiで送信
}

onMounted(() => {
    init()
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
    <p>{{ selectedCategory }}</p>
    <v-list
        v-model:selected="settingsSelection"
        lines="three"
        select-strategy="leaf"
        class="px-3"
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
                        density="comfortable"
                        icon="mdi-tag"></v-btn>
                </template>
            </v-list-item>
        </TransitionGroup>
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
</template>

<style lang="scss" scoped>
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

// トランジションのスタイル
.task-list-move,
.task-list-enter-active,
.task-list-leave-active {
    transition: all 0.5s ease;
}

.task-list-enter-from,
.task-list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.task-list-leave-active {
    position: absolute;
}
</style>

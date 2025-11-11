<script setup lang="ts">
import { ref } from 'vue'

interface Todo {
    id: number
    text: string
    completed: boolean
}

const newTodoText = ref('')
const todos = ref<Todo[]>([
    { id: 1, text: 'サンプルタスク1', completed: false },
    { id: 2, text: 'サンプルタスク2', completed: true },
    { id: 3, text: 'サンプルタスク3', completed: false },
])
let nextId = 4

const addTodo = () => {
    if (newTodoText.value.trim()) {
        todos.value.push({
            id: nextId++,
            text: newTodoText.value.trim(),
            completed: false,
        })
        newTodoText.value = ''
    }
}

const toggleTodo = (id: number) => {
    const todo = todos.value.find((t) => t.id === id)
    if (todo) {
        todo.completed = !todo.completed
    }
}
</script>

<template>
    <v-card
        class="mx-auto"
        max-width="600">
        <v-card-title class="bg-primary">
            <span class="text-h5">TODOリスト</span>
        </v-card-title>

        <v-card-text class="pa-4">
            <!-- タスク登録フォーム -->
            <div class="d-flex gap-2 mb-4">
                <v-text-field
                    v-model="newTodoText"
                    label="新しいタスク"
                    placeholder="タスクを入力してください"
                    variant="outlined"
                    density="comfortable"
                    hide-details
                    @keyup.enter="addTodo" />
                <v-btn
                    color="primary"
                    size="large"
                    @click="addTodo">
                    追加
                </v-btn>
            </div>

            <!-- タスクリスト -->
            <v-list>
                <v-list-item
                    v-for="todo in todos"
                    :key="todo.id"
                    class="px-0"
                    :class="{ 'text-decoration-line-through text-grey': todo.completed }">
                    <template #prepend>
                        <v-checkbox
                            :model-value="todo.completed"
                            hide-details
                            @update:model-value="toggleTodo(todo.id)" />
                    </template>

                    <v-list-item-title>
                        {{ todo.text }}
                    </v-list-item-title>
                </v-list-item>

                <v-list-item
                    v-if="todos.length === 0"
                    class="text-center text-grey">
                    タスクがありません
                </v-list-item>
            </v-list>
        </v-card-text>

        <v-card-actions class="pa-4 pt-0">
            <v-spacer />
            <span class="text-caption text-grey">完了: {{ todos.filter((t) => t.completed).length }} / {{ todos.length }}</span>
        </v-card-actions>
    </v-card>
</template>

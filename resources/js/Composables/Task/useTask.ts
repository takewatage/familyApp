import { ref } from 'vue'
import { familyTaskApi } from '@/Api/familyTaskApi'
import type { TaskData } from '@/Types/dto.generated'

export const useTask = (initialTasks: TaskData[] = []) => {
    const tasks = ref<TaskData[]>(initialTasks)

    const toggleTask = async (task: TaskData) => {
        const index = tasks.value.findIndex((t) => t.id === task.id)
        const backup = index !== -1 ? { ...tasks.value[index] } : null
        if (index !== -1) {
            tasks.value[index].isCompleted = !tasks.value[index].isCompleted
            tasks.value[index].completedAt = tasks.value[index].isCompleted ? new Date().toISOString() : null
        }
        try {
            const res = await familyTaskApi.toggle(task.id)
            if (index !== -1) {
                tasks.value[index] = { ...tasks.value[index], ...res.data.task }
            }
        } catch (error) {
            // エラー時はロールバック
            if (index !== -1 && backup) {
                tasks.value[index] = backup
            }
            console.error('Failed to toggle task:', error)
        }
    }

    const addTask = (newTask: TaskData) => {
        if (!tasks.value.find((t) => t.id === newTask.id)) {
            tasks.value.push(newTask)
        }
    }

    const updateTask = (updatedTask: TaskData) => {
        const index = tasks.value.findIndex((t) => t.id === updatedTask.id)
        if (index !== -1) {
            tasks.value[index] = { ...updatedTask }
        }
    }

    const deleteTask = async (task: TaskData): Promise<boolean> => {
        const backup = [...tasks.value]
        tasks.value = tasks.value.filter((t) => t.id !== task.id)
        try {
            await familyTaskApi.destroy(task.id)
            return true
        } catch (error) {
            // エラー時はロールバック
            tasks.value = backup
            console.error('Failed to delete task:', error)
            return false
        }
    }

    const removeTask = (taskId: string) => {
        tasks.value = tasks.value.filter((t) => t.id !== taskId)
    }

    return { tasks, toggleTask, addTask, updateTask, deleteTask, removeTask }
}

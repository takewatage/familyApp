import { ref, reactive } from 'vue'
import axios from 'axios'
import type { TaskData } from '@/Types/dto.generated'

type TaskForm = {
    content: string
    categoryId: string
    memo: string
}

export const useTaskForm = () => {
    const addTaskOpen = ref(false)
    const isSubmitting = ref(false)

    const form = reactive<TaskForm>({
        content: '',
        categoryId: '',
        memo: '',
    })

    const openAddTaskSheet = (categoryId?: string) => {
        if (categoryId) {
            form.categoryId = categoryId
        }
        addTaskOpen.value = true
    }

    const closeSheet = () => {
        addTaskOpen.value = false
        resetForm()
    }

    const resetForm = () => {
        form.content = ''
    }

    const submitTask = async (): Promise<TaskData | null> => {
        if (!form.content.trim() || !form.categoryId) {
            return null
        }

        isSubmitting.value = true

        try {
            const response = await axios.post('/tasks', {
                content: form.content,
                category_id: form.categoryId,
            })

            closeSheet()
            return response.data.task as TaskData
        } catch (error) {
            console.error('Failed to create task:', error)
            return null
        } finally {
            isSubmitting.value = false
        }
    }

    return {
        addTaskOpen,
        isSubmitting,
        taskForm: form,
        openAddTaskSheet,
        closeSheet,
        resetForm,
        submitTask,
    }
}

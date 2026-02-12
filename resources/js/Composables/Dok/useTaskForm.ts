import { ref, reactive } from 'vue'
import axios from 'axios'
import type { TaskData } from '@/Types/dto.generated'

type TaskForm = {
    content: string
    categoryId: string
}

export const useTaskForm = () => {
    const isOpen = ref(false)
    const isSubmitting = ref(false)

    const form = reactive<TaskForm>({
        content: '',
        categoryId: '',
    })

    const openSheet = (categoryId?: string) => {
        if (categoryId) {
            form.categoryId = categoryId
        }
        isOpen.value = true
    }

    const closeSheet = () => {
        isOpen.value = false
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
        isOpen,
        isSubmitting,
        form,
        openSheet,
        closeSheet,
        resetForm,
        submitTask,
    }
}

import axios from 'axios'
import type {
    SortTaskCategoryRequest,
    StoreTaskCategoryRequest,
    TaskCategoryResult,
    UpdateTaskCategoryRequest,
} from '@/Types/dto.generated'

export const taskCategoryApi = {
    store(data: StoreTaskCategoryRequest) {
        return axios.post<TaskCategoryResult>('/task-categories', data)
    },
    update(id: string, data: UpdateTaskCategoryRequest) {
        return axios.patch<TaskCategoryResult>(`/task-categories/${id}`, data)
    },
    destroy(id: string) {
        return axios.delete<{ success: boolean }>(`/task-categories/${id}`)
    },
    reorder(data: SortTaskCategoryRequest) {
        return axios.post<{ success: boolean }>('/task-categories/reorder', data)
    },
}

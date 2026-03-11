import client from './client'
import { SaveTaskRequestData, TaskData } from '@/Types/dto.generated'

export const familyTaskApi = {
    store(data: SaveTaskRequestData) {
        return client.post<{ task: TaskData }>('/task', data)
    },
    update(taskId: string, data: SaveTaskRequestData) {
        return client.patch<{ task: TaskData }>(`/task/${taskId}`, data)
    },
    toggle(taskId: string) {
        return client.patch<{ task: TaskData }>(`/task/${taskId}/toggle`)
    },
    destroy(taskId: string) {
        return client.delete(`/task/${taskId}`)
    },
    destroyCompleted(categoryId: string) {
        return client.delete('/tasks/completed', { data: { categoryId } })
    },
}

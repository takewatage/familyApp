import client from './client'
import { TaskData } from '@/Types/dto.generated'

export const familyTaskApi = {
    store(data: { content: string; category_id: string; color: string; memo: string }) {
        return client.post<{ task: TaskData }>('/tasks', data)
    },
    update(taskId: string, data: { content: string; category_id: string; color: string; memo: string }) {
        return client.patch<{ task: TaskData }>(`/tasks/${taskId}`, data)
    },
    toggle(taskId: string) {
        return client.patch<TaskData>(`/tasks/${taskId}/toggle`)
    },
    destroy(taskId: string) {
        return client.delete(`/tasks/${taskId}`)
    },
}

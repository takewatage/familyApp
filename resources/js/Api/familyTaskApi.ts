import client from './client'
import { SaveTaskData, TaskData } from '@/Types/dto.generated'

export const familyTaskApi = {
    store(data: SaveTaskData) {
        return client.post<{ task: TaskData }>('/task', data)
    },
    update(taskId: string, data: SaveTaskData) {
        return client.patch<{ task: TaskData }>(`/task/${taskId}`, data)
    },
    toggle(taskId: string) {
        return client.patch<{ task: TaskData }>(`/task/${taskId}/toggle`)
    },
    destroy(taskId: string) {
        return client.delete(`/task/${taskId}`)
    },
}

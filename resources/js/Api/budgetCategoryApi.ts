import axios from 'axios'
import type { ReorderCategoriesRequest } from '@/Types/dto.generated'

/**
 * カテゴリー並び替え API。
 * CRUD（store/update/destroy）はバリデーションエラーをフォームに出すため Inertia を使うが、
 * 並び替えは画面遷移不要の静かな更新のため axios + JSON を使う（既存 taskCategoryApi.reorder と同方針）。
 */
export const budgetCategoryApi = {
    reorder(data: ReorderCategoriesRequest) {
        return axios.post<{ success: boolean }>('/budget/categories/reorder', data)
    },
}

import axios from 'axios'

/**
 * クイック入力の利用回数加算 API。
 * CRUD（store/update/destroy）はバリデーションエラーをフォームに出すため Inertia を使うが、
 * 支出フォームへのプリセット時の利用回数加算は画面遷移不要の静かな更新のため axios + JSON を使う。
 */
export const budgetQuickEntryApi = {
    use(id: string) {
        return axios.post<{ success: boolean }>(`/budget/quick-entries/${id}/use`, undefined, {
            suppressToast: true,
        })
    },
}

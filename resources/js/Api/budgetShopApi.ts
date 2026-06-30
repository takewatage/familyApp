import axios from 'axios'
import type { ShopData } from '@/Types/dto.generated'

/**
 * 店舗オートコンプリート API。
 * 支出フォームの店名候補を家族スコープ・利用回数降順でインクリメンタル検索する（CRUD は Inertia）。
 */
export const budgetShopApi = {
    search(keyword: string) {
        return axios.get<ShopData[]>('/budget/shops/search', {
            params: { q: keyword },
            suppressToast: true,
        })
    },
}

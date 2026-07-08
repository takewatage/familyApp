import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * 家計簿の各画面（支出一覧・予算設定・ダッシュボード）で共通利用する月切替ロジック。
 * `YYYY-MM` の前後移動・「YYYY年M月」ラベル整形・`?month=` 付き遷移を 1 箇所に集約する。
 *
 * @param currentYearMonth 現在表示中の年月を返すゲッター（`() => props.value.yearMonth`）
 * @param routeName 月切替時に遷移する Ziggy ルート名
 */
export function useMonthNavigation(currentYearMonth: () => string, routeName: string) {
    const monthLabel = computed(() => {
        const [y, m] = currentYearMonth().split('-')

        return `${y}年${Number(m)}月`
    })

    function shiftMonth(ym: string, delta: number): string {
        const [y, m] = ym.split('-').map(Number)
        const d = new Date(y, m - 1 + delta, 1)

        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    }

    function goMonth(delta: number) {
        router.get(
            route(routeName),
            { month: shiftMonth(currentYearMonth(), delta) },
            { preserveScroll: true, preserveState: false },
        )
    }

    return { monthLabel, goMonth }
}

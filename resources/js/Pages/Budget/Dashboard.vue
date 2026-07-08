<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useMonthNavigation } from '@/Composables/Budget/useMonthNavigation'
import { formatYen } from '@/Utils/currencyFormatter'
import type { BudgetDashboardPageResult, CategoryUsageData, RecurringReminderData } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<BudgetDashboardPageResult>()

// ----- 月切替 -----
const { monthLabel, goMonth } = useMonthNavigation(() => props.value.yearMonth, 'budget.dashboard')

const calc = computed(() => props.value.calculation)

// カテゴリー参照（名前・色・アイコン・親子表示用）
const categoryById = computed(() => {
    const map: Record<string, (typeof props.value.categories)[number]> = {}
    props.value.categories.forEach((c) => {
        map[c.id] = c
    })

    return map
})

// 残高サマリーのタイル定義（color 未指定はデフォルト表示、負値になりうる 2 タイルのみ着色）
interface SummaryTile {
    label: string
    value: string
    color?: string
}

const summaryTiles = computed<SummaryTile[]>(() => [
    { label: '月収入', value: formatYen(calc.value.totalIncome) },
    { label: '実支出', value: formatYen(calc.value.totalExpense) },
    { label: '固定費', value: formatYen(calc.value.fixedCostTotal) },
    { label: '貯金目標', value: formatYen(calc.value.savingTarget) },
    {
        label: '自由に使えるお金',
        value: formatYen(calc.value.discretionary),
        color: Number(calc.value.discretionary) < 0 ? 'error' : 'primary',
    },
    {
        label: '貯金可能額',
        value: formatYen(calc.value.possibleSaving),
        color: Number(calc.value.possibleSaving) < 0 ? 'error' : 'success',
    },
])

// 消化率（%）を 0-100 にクランプしてプログレスバー用に返す。
function percentValue(usagePercent?: string | null): number {
    if (usagePercent === null || usagePercent === undefined) {
        return 0
    }

    return Math.min(100, Math.max(0, Number(usagePercent)))
}

// 消化率に応じたバー色（80%以上=警告 / 100%超=危険。ちょうど 100% は予算内なので警告に留める）
function percentColor(usagePercent?: string | null): string {
    if (usagePercent === null || usagePercent === undefined) {
        return 'grey'
    }

    const value = Number(usagePercent)

    if (value > 100) {
        return 'error'
    }

    if (value >= 80) {
        return 'warning'
    }

    return 'primary'
}

function categoryName(row: CategoryUsageData): string {
    return categoryById.value[row.categoryId]?.name ?? '（未分類）'
}

// 予算が設定されているカテゴリーを先頭に、消化率の高い順で並べる。
const sortedCategories = computed(() =>
    [...calc.value.categories].sort((a, b) => Number(b.usagePercent ?? -1) - Number(a.usagePercent ?? -1)),
)

// リマインダーは 遅延 → 期日接近 → その他 の順で強調する。
function reminderRank(r: RecurringReminderData): number {
    if (r.isOverdue) {
        return 0
    }

    if (r.isUpcoming) {
        return 1
    }

    return r.isPaid ? 3 : 2
}

const sortedReminders = computed(() =>
    [...props.value.reminders].sort(
        (a, b) => reminderRank(a) - reminderRank(b) || a.paymentDate.localeCompare(b.paymentDate),
    ),
)

function paymentDayLabel(paymentDate: string): string {
    const day = Number(paymentDate.split('-')[2])

    return `${day}日`
}

function goTo(routeName: string) {
    router.visit(route(routeName))
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="10"
                md="8">
                <!-- 月切替ヘッダー -->
                <div class="d-flex align-center justify-space-between mb-4">
                    <v-btn
                        icon="mdi-chevron-left"
                        variant="text"
                        @click="goMonth(-1)"/>
                    <div class="text-h6">{{ monthLabel }} の家計簿</div>
                    <v-btn
                        icon="mdi-chevron-right"
                        variant="text"
                        @click="goMonth(1)"/>
                </div>

                <!-- 全体消化率 -->
                <v-card class="mb-4">
                    <v-card-text>
                        <div class="d-flex align-center justify-space-between mb-1">
                            <span class="text-subtitle-2">今月の消化率（実支出 / 収入）</span>
                            <span class="text-subtitle-1 font-weight-bold">
                                {{ calc.overallUsagePercent ? `${calc.overallUsagePercent}%` : '—' }}
                            </span>
                        </div>
                        <v-progress-linear
                            :model-value="percentValue(calc.overallUsagePercent)"
                            :color="percentColor(calc.overallUsagePercent)"
                            height="10"
                            rounded/>
                    </v-card-text>
                </v-card>

                <!-- 残高サマリー -->
                <v-row class="mb-1">
                    <v-col
                        v-for="tile in summaryTiles"
                        :key="tile.label"
                        cols="6"
                        sm="4">
                        <v-card
                            variant="tonal"
                            class="h-100">
                            <v-card-text class="py-3">
                                <div class="text-caption text-medium-emphasis">{{ tile.label }}</div>
                                <div
                                    class="text-subtitle-1 font-weight-bold"
                                    :class="tile.color ? `text-${tile.color}` : ''">
                                    {{ tile.value }}
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- カテゴリー別消化率 -->
                <v-card class="mb-4">
                    <v-card-title class="text-subtitle-1">カテゴリー別消化率</v-card-title>
                    <v-card-text>
                        <div
                            v-if="!sortedCategories.length"
                            class="text-body-2 text-medium-emphasis text-center py-4">
                            今月の予算・支出がまだありません。
                        </div>

                        <div
                            v-for="row in sortedCategories"
                            :key="row.categoryId"
                            class="mb-3">
                            <div class="d-flex align-center mb-1">
                                <v-avatar
                                    :color="categoryById[row.categoryId]?.color"
                                    size="24"
                                    class="mr-2">
                                    <v-icon
                                        v-if="categoryById[row.categoryId]?.icon"
                                        :icon="`mdi-${categoryById[row.categoryId]?.icon}`"
                                        size="14"
                                        color="white"/>
                                </v-avatar>
                                <span class="text-body-2">{{ categoryName(row) }}</span>
                                <v-spacer/>
                                <span class="text-caption text-medium-emphasis mr-2">
                                    {{ formatYen(row.actualAmount) }} / {{ formatYen(row.budgetAmount) }}
                                </span>
                                <span class="text-body-2 font-weight-medium">
                                    {{ row.usagePercent ? `${row.usagePercent}%` : '—' }}
                                </span>
                            </div>
                            <v-progress-linear
                                :model-value="percentValue(row.usagePercent)"
                                :color="percentColor(row.usagePercent)"
                                height="6"
                                rounded/>
                            <div
                                v-if="Number(row.remaining) < 0"
                                class="text-caption text-error mt-1">
                                予算を {{ formatYen(Math.abs(Number(row.remaining))) }} 超過
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- 固定費リマインダー -->
                <v-card class="mb-4">
                    <v-card-title class="text-subtitle-1">今月の固定費</v-card-title>
                    <v-card-text>
                        <div
                            v-if="!sortedReminders.length"
                            class="text-body-2 text-medium-emphasis text-center py-4">
                            今月の固定費はありません。
                        </div>

                        <v-list
                            v-else
                            density="compact"
                            class="py-0">
                            <v-list-item
                                v-for="r in sortedReminders"
                                :key="r.id"
                                class="px-0">
                                <template #prepend>
                                    <v-icon
                                        v-if="r.isPaid"
                                        icon="mdi-check-circle"
                                        color="success"/>
                                    <v-icon
                                        v-else-if="r.isOverdue"
                                        icon="mdi-alert-circle"
                                        color="error"/>
                                    <v-icon
                                        v-else-if="r.isUpcoming"
                                        icon="mdi-clock-alert-outline"
                                        color="warning"/>
                                    <v-icon
                                        v-else
                                        icon="mdi-calendar-blank-outline"
                                        color="grey"/>
                                </template>

                                <v-list-item-title class="text-body-2">
                                    {{ r.name }}
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ paymentDayLabel(r.paymentDate) }} 支払い
                                    <span
                                        v-if="r.isOverdue"
                                        class="text-error">・未払い</span>
                                    <span
                                        v-else-if="r.isUpcoming"
                                        class="text-warning">・まもなく</span>
                                    <span
                                        v-else-if="r.isPaid"
                                        class="text-medium-emphasis">・支払済み</span>
                                </v-list-item-subtitle>

                                <template #append>
                                    <span class="text-body-2 font-weight-medium">{{ formatYen(r.amount) }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>

                <!-- 導線 -->
                <div class="d-flex ga-2">
                    <v-btn
                        color="primary"
                        variant="flat"
                        prepend-icon="mdi-plus"
                        @click="goTo('budget.expenses.index')">
                        支出を登録
                    </v-btn>
                    <v-btn
                        variant="tonal"
                        prepend-icon="mdi-cog-outline"
                        @click="goTo('budget.budgets.show')">
                        予算設定
                    </v-btn>
                </div>
            </v-col>
        </v-row>
    </v-container>
</template>

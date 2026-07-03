<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import type {
    BudgetSettingsPageResult,
    StoreBudgetRequest,
    StoreBudgetCategoriesRequest,
    StoreBudgetAlertsRequest,
} from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<BudgetSettingsPageResult>()
const snackbar = useSnackbar()

// ----- 月切替 -----
const monthLabel = computed(() => {
    const [y, m] = props.value.yearMonth.split('-')

    return `${y}年${Number(m)}月`
})

function shiftMonth(ym: string, delta: number): string {
    const [y, m] = ym.split('-').map(Number)
    const d = new Date(y, m - 1 + delta, 1)

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function goMonth(delta: number) {
    router.get(
        route('budget.budgets.show'),
        { month: shiftMonth(props.value.yearMonth, delta) },
        { preserveScroll: true, preserveState: false },
    )
}

// カテゴリー参照（名前・色・親子表示用）
const categoryById = computed(() => {
    const map: Record<string, (typeof props.value.categories)[number]> = {}
    props.value.categories.forEach((c) => {
        map[c.id] = c
    })

    return map
})

// 全体（null）＋カテゴリーのアラート対象選択肢
const alertTargetItems = computed(() => [
    { value: null as string | null, title: '家族全体' },
    ...props.value.categories.map((c) => ({
        value: c.id as string | null,
        title: c.parentId ? `└ ${c.name}` : c.name,
    })),
])

// ----- 月収入・貯金目標（F-9） -----
const budgetForm = useInertiaForm<StoreBudgetRequest>({
    yearMonth: props.value.yearMonth,
    totalIncome: props.value.totalIncome,
    savingTarget: props.value.savingTarget,
})

function submitBudget() {
    budgetForm.post(route('budget.budgets.store'), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('月収入・貯金目標を保存しました'),
        onError: () => snackbar.error('保存に失敗しました'),
    })
}

// ----- カテゴリー別予算（F-10） -----
// カテゴリー並び順に合わせて初期化し、既存予算があれば充当する。
const categoryForm = useInertiaForm<StoreBudgetCategoriesRequest>({
    yearMonth: props.value.yearMonth,
    categoryBudgets: props.value.categories.map((c) => ({
        categoryId: c.id,
        amount: props.value.categoryBudgets.find((cb) => cb.categoryId === c.id)?.amount ?? '0',
    })),
})

function submitCategories() {
    categoryForm.post(route('budget.budgets.categories'), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('カテゴリー別予算を保存しました'),
        onError: () => snackbar.error('保存に失敗しました'),
    })
}

// ----- アラート設定（F-12） -----
// 表示行は uid 付きで管理し、v-for のキーに使う（index キーだと splice 削除で行の入力状態がずれる）。
interface AlertRow {
    uid: number
    categoryId: string | null
    thresholdPercent: number
    isEnabled: boolean
}

let alertUidSeq = 0
const alertRows = ref<AlertRow[]>(
    props.value.alerts.map((a) => ({
        uid: alertUidSeq++,
        categoryId: a.categoryId ?? null,
        thresholdPercent: a.thresholdPercent,
        isEnabled: a.isEnabled,
    })),
)

// 送信は表示行から uid を除いて組み立てる（errors/processing のため form は保持）
const alertForm = useInertiaForm<StoreBudgetAlertsRequest>({ alerts: [] })

function addAlert() {
    alertRows.value.push({ uid: alertUidSeq++, categoryId: null, thresholdPercent: 80, isEnabled: true })
}

function removeAlert(index: number) {
    alertRows.value.splice(index, 1)
}

// 各行の選択肢から「他行が既に使っているターゲット」を除外し、重複作成を防ぐ（サーバー側 distinct と二重防御）。
function alertItemsFor(row: AlertRow) {
    const usedByOthers = new Set(
        alertRows.value.filter((r) => r !== row).map((r) => r.categoryId),
    )

    return alertTargetItems.value.filter(
        (item) => item.value === row.categoryId || !usedByOthers.has(item.value),
    )
}

function submitAlerts() {
    alertForm.alerts = alertRows.value.map((r) => ({
        categoryId: r.categoryId,
        thresholdPercent: r.thresholdPercent,
        isEnabled: r.isEnabled,
    }))
    alertForm.post(route('budget.budgets.alerts'), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('アラート設定を保存しました'),
        onError: () => snackbar.error('保存に失敗しました'),
    })
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
                    <div class="text-h6">{{ monthLabel }} の予算</div>
                    <v-btn
                        icon="mdi-chevron-right"
                        variant="text"
                        @click="goMonth(1)"/>
                </div>

                <!-- 月収入・貯金目標 -->
                <v-card class="mb-4">
                    <v-card-title class="text-subtitle-1">月収入・貯金目標</v-card-title>
                    <v-card-text>
                        <v-text-field
                            v-model="budgetForm.totalIncome"
                            label="月収入"
                            type="number"
                            inputmode="numeric"
                            prefix="¥"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="budgetForm.errors.totalIncome"/>

                        <v-text-field
                            v-model="budgetForm.savingTarget"
                            label="貯金目標"
                            type="number"
                            inputmode="numeric"
                            prefix="¥"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="budgetForm.errors.savingTarget"
                            class="mt-2"/>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="budgetForm.processing"
                            @click="submitBudget">
                            保存
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <!-- カテゴリー別予算 -->
                <v-card class="mb-4">
                    <v-card-title class="text-subtitle-1">カテゴリー別予算</v-card-title>
                    <v-card-text>
                        <div
                            v-for="row in categoryForm.categoryBudgets"
                            :key="row.categoryId"
                            class="d-flex align-center mb-2">
                            <v-avatar
                                :color="categoryById[row.categoryId]?.color"
                                size="28"
                                class="mr-2">
                                <v-icon
                                    v-if="categoryById[row.categoryId]?.icon"
                                    :icon="`mdi-${categoryById[row.categoryId]?.icon}`"
                                    size="16"
                                    color="white"/>
                            </v-avatar>
                            <div
                                class="flex-grow-1 text-body-2"
                                :class="{ 'text-medium-emphasis': categoryById[row.categoryId]?.parentId }">
                                <span v-if="categoryById[row.categoryId]?.parentId">└ </span>
                                {{ categoryById[row.categoryId]?.name }}
                            </div>
                            <v-text-field
                                v-model="row.amount"
                                type="number"
                                inputmode="numeric"
                                prefix="¥"
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="max-width: 160px"/>
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="categoryForm.processing"
                            @click="submitCategories">
                            保存
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <!-- アラート設定 -->
                <v-card class="mb-4">
                    <v-card-title class="d-flex align-center text-subtitle-1">
                        予算アラート
                        <v-spacer/>
                        <v-btn
                            variant="text"
                            size="small"
                            prepend-icon="mdi-plus"
                            @click="addAlert">
                            追加
                        </v-btn>
                    </v-card-title>
                    <v-card-text>
                        <div
                            v-if="!alertRows.length"
                            class="text-body-2 text-medium-emphasis text-center py-4">
                            アラート未設定です。「追加」で消化率の通知条件を設定できます。
                        </div>

                        <div
                            v-for="(alert, i) in alertRows"
                            :key="alert.uid"
                            class="d-flex align-center mb-2">
                            <v-select
                                v-model="alert.categoryId"
                                :items="alertItemsFor(alert)"
                                label="対象"
                                variant="outlined"
                                density="compact"
                                hide-details
                                class="mr-2"
                                style="max-width: 180px"/>
                            <v-text-field
                                v-model.number="alert.thresholdPercent"
                                label="閾値"
                                type="number"
                                inputmode="numeric"
                                suffix="%"
                                variant="outlined"
                                density="compact"
                                hide-details
                                style="max-width: 110px"/>
                            <v-switch
                                v-model="alert.isEnabled"
                                color="primary"
                                hide-details
                                density="compact"
                                class="ml-2"/>
                            <v-btn
                                icon="mdi-delete-outline"
                                variant="text"
                                size="small"
                                color="error"
                                @click="removeAlert(i)"/>
                        </div>
                        <div class="text-caption text-medium-emphasis mt-1">
                            消化率が閾値に達すると通知します（同一月・同一アラートで重複通知しません）。
                        </div>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer/>
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="alertForm.processing"
                            @click="submitAlerts">
                            保存
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

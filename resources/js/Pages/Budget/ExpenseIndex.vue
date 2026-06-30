<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import ExpenseForm from '@/Components/Budget/ExpenseForm.vue'
import { formatDateShort } from '@/Utils/dateFormatter'
import type { ExpenseData, ExpensePageResult } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<ExpensePageResult>()
const { confirm } = useConfirmDialog()
const snackbar = useSnackbar()

const dialog = ref(false)
const editing = ref<ExpenseData | null>(null)
const duplicating = ref<ExpenseData | null>(null)
// ダイアログ内 ExpenseForm を開くたびに再マウントして初期値をリセットする
const formKey = ref(0)

const monthLabel = computed(() => {
    const [y, m] = props.value.yearMonth.split('-')

    return `${y}年${Number(m)}月`
})

const totalLabel = computed(() => `¥${Number(props.value.totalAmount).toLocaleString()}`)

function shiftMonth(ym: string, delta: number): string {
    const [y, m] = ym.split('-').map(Number)
    const d = new Date(y, m - 1 + delta, 1)

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
}

function goMonth(delta: number) {
    router.get(
        route('budget.expenses.index'),
        { month: shiftMonth(props.value.yearMonth, delta) },
        { preserveScroll: true, preserveState: false },
    )
}

function openCreate() {
    editing.value = null
    duplicating.value = null
    formKey.value++
    dialog.value = true
}

function openEdit(expense: ExpenseData) {
    editing.value = expense
    duplicating.value = null
    formKey.value++
    dialog.value = true
}

// 履歴からの再登録（F-4）: 内容を複製して新規登録（日付は当日）
function openDuplicate(expense: ExpenseData) {
    editing.value = null
    duplicating.value = expense
    formKey.value++
    dialog.value = true
}

function onSaved(expenseDate: string) {
    dialog.value = false

    const savedMonth = expenseDate.slice(0, 7)

    // 別の月の支出を保存した場合はその月へ切り替えて結果を見せる（同月なら部分リロード）
    if (savedMonth !== props.value.yearMonth) {
        router.get(
            route('budget.expenses.index'),
            { month: savedMonth },
            { preserveScroll: true, preserveState: false },
        )

        return
    }

    router.reload({ only: ['expenses', 'totalAmount', 'shops'] })
}

async function remove(expense: ExpenseData) {
    const ok = await confirm({
        title: '支出を削除しますか？',
        message: 'この操作は取り消せません。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('budget.expenses.destroy', expense.id), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('支出を削除しました'),
        onError: () => snackbar.error('削除に失敗しました'),
    })
}

function amountLabel(amount: string): string {
    return `¥${Number(amount).toLocaleString()}`
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="10"
                md="8">
                <!-- 月切替 + 合計 -->
                <div class="d-flex align-center justify-space-between mb-4">
                    <v-btn
                        icon="mdi-chevron-left"
                        variant="text"
                        @click="goMonth(-1)"/>
                    <div class="text-center">
                        <div class="text-h6">{{ monthLabel }}</div>
                        <div class="text-body-2 text-medium-emphasis">合計 {{ totalLabel }}</div>
                    </div>
                    <v-btn
                        icon="mdi-chevron-right"
                        variant="text"
                        @click="goMonth(1)"/>
                </div>

                <!-- 支出一覧 -->
                <v-card v-if="props.expenses.length">
                    <v-list lines="two">
                        <template
                            v-for="(expense, i) in props.expenses"
                            :key="expense.id">
                            <v-divider v-if="i > 0"/>
                            <v-list-item @click="openEdit(expense)">
                                <template #prepend>
                                    <v-avatar
                                        :color="expense.categoryColor"
                                        size="40">
                                        <v-icon
                                            v-if="expense.categoryIcon"
                                            :icon="`mdi-${expense.categoryIcon}`"
                                            color="white"/>
                                        <span
                                            v-else
                                            class="text-white text-caption">
                                            {{ expense.categoryName.slice(0, 1) }}
                                        </span>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="d-flex align-center">
                                    <span>{{ expense.shopDisplayName || expense.categoryName }}</span>
                                    <v-chip
                                        v-if="expense.isRecurring"
                                        size="x-small"
                                        color="primary"
                                        class="ml-2">
                                        固定費
                                    </v-chip>
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ formatDateShort(expense.expenseDate) }} ・ {{ expense.categoryName }} ・
                                    {{ expense.paymentMethodName }}
                                    <template v-if="expense.memberName"> ・ {{ expense.memberName }}</template>
                                </v-list-item-subtitle>

                                <template #append>
                                    <div class="d-flex align-center">
                                        <span class="text-subtitle-1 font-weight-bold mr-2">
                                            {{ amountLabel(expense.amount) }}
                                        </span>
                                        <v-btn
                                            icon="mdi-content-copy"
                                            variant="text"
                                            size="small"
                                            title="再登録"
                                            @click.stop="openDuplicate(expense)"/>
                                        <v-btn
                                            icon="mdi-delete-outline"
                                            variant="text"
                                            size="small"
                                            color="error"
                                            @click.stop="remove(expense)"/>
                                    </div>
                                </template>
                            </v-list-item>
                        </template>
                    </v-list>
                </v-card>

                <v-card
                    v-else
                    variant="tonal"
                    class="text-center pa-8">
                    <v-icon
                        icon="mdi-receipt-text-outline"
                        size="48"
                        class="mb-2"/>
                    <div class="text-body-1">この月の支出はまだありません</div>
                </v-card>
            </v-col>
        </v-row>

        <!-- 追加 FAB -->
        <v-btn
            icon="mdi-plus"
            color="primary"
            size="large"
            class="expense-fab"
            @click="openCreate"/>

        <!-- 登録 / 編集ダイアログ -->
        <v-dialog
            v-model="dialog"
            max-width="500"
            persistent>
            <v-card>
                <v-card-title>
                    {{ editing ? '支出を編集' : duplicating ? '支出を再登録' : '支出を登録' }}
                </v-card-title>
                <v-card-text>
                    <ExpenseForm
                        :key="formKey"
                        :categories="props.categories"
                        :payment-methods="props.paymentMethods"
                        :shops="props.shops"
                        :member-options="props.memberOptions"
                        :expense="editing"
                        :duplicate-from="duplicating"
                        @saved="onSaved"
                        @cancel="dialog = false"/>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<style lang="scss" scoped>
.expense-fab {
    position: fixed;
    right: 16px;
    bottom: 80px;
    z-index: 5;
}
</style>

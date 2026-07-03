<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import dayjs from 'dayjs'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { formatYen } from '@/Utils/currencyFormatter'
import type {
    RecurringExpensesPageResult,
    RecurringExpenseData,
    StoreRecurringExpenseRequest,
} from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<RecurringExpensesPageResult>()
const { confirm } = useConfirmDialog()
const snackbar = useSnackbar()

// カテゴリーは親子をインデント表示（並びは sort_order 済み）
const categoryItems = computed(() =>
    props.value.categories.map((c) => ({
        value: c.id,
        title: c.parentId ? `└ ${c.name}` : c.name,
    })),
)

const shopItems = computed(() =>
    props.value.shops.map((s) => ({ value: s.id, title: s.name })),
)

// ----- 登録 / 編集ダイアログ -----
const dialog = ref(false)
const editing = ref<RecurringExpenseData | null>(null)
const formKey = ref(0)

const dialogTitle = computed(() =>
    editing.value ? '繰り返し支出を編集' : '繰り返し支出を追加',
)

const form = useInertiaForm<StoreRecurringExpenseRequest>({
    name: '',
    amount: '',
    categoryId: '',
    paymentMethodId: '',
    dayOfMonth: 1,
    startDate: dayjs().format('YYYY-MM-DD'),
    endDate: undefined,
    shopId: undefined,
    memberType: undefined,
    memberId: undefined,
})

// 担当者（実ユーザー / 仮想ユーザー）。key = "{memberType}|{memberId}"
// stale な担当者（家族から外れた）は選択肢に存在しないため未選択扱いにする。
const memberKey = ref<string | null>(null)

watch(memberKey, (key) => {
    if (!key) {
        form.memberType = undefined
        form.memberId = undefined

        return
    }

    const opt = props.value.memberOptions.find((m) => m.key === key)
    form.memberType = opt?.memberType
    form.memberId = opt?.memberId
})

function openCreate() {
    editing.value = null
    form.reset()
    memberKey.value = null
    formKey.value++
    dialog.value = true
}

function openEdit(recurring: RecurringExpenseData) {
    editing.value = recurring
    form.name = recurring.name
    form.amount = recurring.amount
    form.categoryId = recurring.categoryId
    form.paymentMethodId = recurring.paymentMethodId
    form.dayOfMonth = recurring.dayOfMonth
    form.startDate = recurring.startDate
    form.endDate = recurring.endDate ?? undefined
    form.shopId = recurring.shopId ?? undefined

    const key
        = recurring.memberType && recurring.memberId
            ? `${recurring.memberType}|${recurring.memberId}`
            : null
    memberKey.value = props.value.memberOptions.some((m) => m.key === key) ? key : null

    formKey.value++
    dialog.value = true
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success(
                editing.value ? '繰り返し支出を更新しました' : '繰り返し支出を追加しました',
            )
            dialog.value = false
        },
        onError: () => snackbar.error('保存に失敗しました'),
    }

    if (editing.value) {
        form.patch(route('budget.recurring-expenses.update', editing.value.id), options)
    } else {
        form.post(route('budget.recurring-expenses.store'), options)
    }
}

async function deactivate(recurring: RecurringExpenseData) {
    const ok = await confirm({
        title: '繰り返し支出を無効化しますか？',
        message: '以降は自動生成されなくなります（生成済みの支出には影響しません）。',
        confirmText: '無効化する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('budget.recurring-expenses.destroy', recurring.id), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('繰り返し支出を無効化しました'),
        onError: () => snackbar.error('無効化に失敗しました'),
    })
}

function periodLabel(recurring: RecurringExpenseData): string {
    return recurring.endDate
        ? `${recurring.startDate} 〜 ${recurring.endDate}`
        : `${recurring.startDate} 〜`
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="10"
                md="8">
                <div class="d-flex align-center justify-space-between mb-4">
                    <h2 class="text-h6">繰り返し支出（固定費）</h2>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openCreate">
                        追加
                    </v-btn>
                </div>

                <v-card v-if="props.recurringExpenses.length">
                    <v-list lines="two">
                        <template
                            v-for="(re, i) in props.recurringExpenses"
                            :key="re.id">
                            <v-divider v-if="i > 0"/>
                            <v-list-item>
                                <template #prepend>
                                    <v-avatar
                                        :color="re.categoryColor"
                                        size="40">
                                        <v-icon
                                            v-if="re.categoryIcon"
                                            :icon="`mdi-${re.categoryIcon}`"
                                            color="white"/>
                                        <span
                                            v-else
                                            class="text-white text-caption">
                                            {{ re.categoryName.slice(0, 1) }}
                                        </span>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="ml-2">
                                    {{ re.name }}
                                    <span class="text-medium-emphasis text-caption ml-1">
                                        毎月 {{ re.dayOfMonth }} 日
                                    </span>
                                </v-list-item-title>
                                <v-list-item-subtitle class="ml-2">
                                    {{ formatYen(re.amount) }} ・ {{ re.categoryName }} ・
                                    {{ re.paymentMethodName }}
                                    <template v-if="re.memberName"> ・ {{ re.memberName }}</template>
                                    <br>
                                    <span class="text-caption">{{ periodLabel(re) }}</span>
                                </v-list-item-subtitle>

                                <template #append>
                                    <div class="d-flex align-center">
                                        <v-btn
                                            icon="mdi-pencil"
                                            variant="text"
                                            size="small"
                                            @click="openEdit(re)"/>
                                        <v-btn
                                            icon="mdi-cancel"
                                            variant="text"
                                            size="small"
                                            color="error"
                                            @click="deactivate(re)"/>
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
                        icon="mdi-calendar-sync-outline"
                        size="48"
                        class="mb-2"/>
                    <div class="text-body-1">登録済みの繰り返し支出はありません</div>
                    <div class="text-body-2 text-medium-emphasis mt-1">
                        家賃・光熱費などの固定費を登録すると、毎月の支払日に支出が自動生成されます
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-dialog
            v-model="dialog"
            max-width="500"
            persistent>
            <v-card :key="formKey">
                <v-card-title>{{ dialogTitle }}</v-card-title>
                <v-card-text>
                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.name"
                            label="支出名"
                            placeholder="例: 家賃・電気代"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.name"/>

                        <v-text-field
                            v-model="form.amount"
                            label="金額"
                            type="number"
                            inputmode="numeric"
                            prefix="¥"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.amount"
                            class="mt-2"/>

                        <v-select
                            v-model="form.categoryId"
                            :items="categoryItems"
                            label="カテゴリー"
                            prepend-inner-icon="mdi-shape"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.categoryId"
                            class="mt-2"/>

                        <v-select
                            v-model="form.paymentMethodId"
                            :items="props.paymentMethods"
                            item-title="name"
                            item-value="id"
                            label="支払い方法"
                            prepend-inner-icon="mdi-credit-card-outline"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.paymentMethodId"
                            class="mt-2"/>

                        <v-select
                            v-model="form.shopId"
                            :items="shopItems"
                            label="店舗（任意）"
                            prepend-inner-icon="mdi-storefront-outline"
                            variant="outlined"
                            density="comfortable"
                            clearable
                            :error-messages="form.errors.shopId"
                            class="mt-2"/>

                        <v-text-field
                            v-model.number="form.dayOfMonth"
                            label="支払日（1-31）"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="31"
                            suffix="日"
                            prepend-inner-icon="mdi-calendar-month"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.dayOfMonth"
                            hint="月末が指定日より前の月は月末に丸められます"
                            persistent-hint
                            class="mt-2"/>

                        <v-text-field
                            v-model="form.startDate"
                            label="開始日"
                            type="date"
                            prepend-inner-icon="mdi-calendar-start"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.startDate"
                            class="mt-2"/>

                        <v-text-field
                            v-model="form.endDate"
                            label="終了日（任意）"
                            type="date"
                            prepend-inner-icon="mdi-calendar-end"
                            variant="outlined"
                            density="comfortable"
                            clearable
                            :error-messages="form.errors.endDate"
                            class="mt-2"/>

                        <v-select
                            v-model="memberKey"
                            :items="props.memberOptions"
                            item-title="name"
                            item-value="key"
                            label="担当者（任意）"
                            prepend-inner-icon="mdi-account"
                            variant="outlined"
                            density="comfortable"
                            clearable
                            class="mt-2"/>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn
                        variant="text"
                        @click="dialog = false">
                        キャンセル
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        :loading="form.processing"
                        @click="submit">
                        {{ editing ? '更新' : '追加' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

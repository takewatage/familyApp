<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { budgetShopApi } from '@/Api/budgetShopApi'
import type {
    CategoryData,
    ExpenseData,
    MemberOptionData,
    PaymentMethodData,
    ShopData,
    StoreExpenseRequest,
} from '@/Types/dto.generated'

const props = defineProps<{
    categories: CategoryData[]
    paymentMethods: PaymentMethodData[]
    shops: ShopData[]
    memberOptions: MemberOptionData[]
    expense?: ExpenseData | null
    // 履歴からの再登録（F-4）: 内容を複製して新規登録する。日付は当日。
    duplicateFrom?: ExpenseData | null
}>()

const emit = defineEmits<{
    // 保存した支出日を渡し、親が別月なら該当月へ切り替えられるようにする
    (e: 'saved', expenseDate: string): void
    (e: 'cancel'): void
}>()

const snackbar = useSnackbar()

const isEdit = computed(() => !!props.expense)
// 編集元（編集 or 再登録の複製元）
const source = props.expense ?? props.duplicateFrom ?? null

function todayStr(): string {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')

    return `${d.getFullYear()}-${m}-${day}`
}

const form = useInertiaForm<StoreExpenseRequest>({
    amount: source?.amount ?? '',
    categoryId: source?.categoryId ?? '',
    paymentMethodId: source?.paymentMethodId ?? '',
    // 再登録時は当日、編集時は元の支出日
    expenseDate: props.expense?.expenseDate ?? todayStr(),
    shopId: source?.shopId ?? undefined,
    shopName: source?.shopName ?? undefined,
    memberType: source?.memberType ?? undefined,
    memberId: source?.memberId ?? undefined,
    memo: source?.memo ?? undefined,
})

// カテゴリーは親子をインデント表示（並びは sort_order 済み）
const categoryItems = computed(() =>
    props.categories.map((c) => ({
        value: c.id,
        title: c.parentId ? `└ ${c.name}` : c.name,
    })),
)

// 担当者（実ユーザー / 仮想ユーザー）。key = "{memberType}|{memberId}"
const memberKey = ref<string | null>(
    source?.memberType && source?.memberId ? `${source.memberType}|${source.memberId}` : null,
)

watch(memberKey, (key) => {
    if (!key) {
        form.memberType = undefined
        form.memberId = undefined

        return
    }

    const opt = props.memberOptions.find((m) => m.key === key)
    form.memberType = opt?.memberType
    form.memberId = opt?.memberId
})

// 店舗: 既存店舗(オブジェクト)選択 or 新規店名(文字列)の手入力を許容
const initialShopModel: ShopData | string | null = source?.shopId
    ? (props.shops.find((s) => s.id === source?.shopId) ?? source?.shopDisplayName ?? null)
    : (source?.shopName ?? null)

const shopModel = ref<ShopData | string | null>(initialShopModel)

// オートコンプリート候補（初期は親から渡された利用頻度上位。入力に応じてサーバー検索で差し替える）
const shopCandidates = ref<ShopData[]>([...props.shops])

let shopSearchSeq = 0

const onShopSearch = useDebounceFn(async (keyword: string) => {
    // 店舗選択直後（検索欄が選択中の店名で埋まる）だけ再検索を抑止する
    if (
        shopModel.value
        && typeof shopModel.value === 'object'
        && shopModel.value.name === keyword
    ) {
        return
    }

    const seq = ++shopSearchSeq

    try {
        const { data } = await budgetShopApi.search(keyword)
        // 後発リクエストが既に走っている場合は古い結果を破棄する（競合防止）
        if (seq === shopSearchSeq) {
            shopCandidates.value = data
        }
    } catch {
        // 検索失敗時は候補を維持（手入力は引き続き可能）
    }
}, 300)

// カテゴリーが店舗の既定値から自動セットされたものか（ユーザーの明示選択と区別する）
const categoryAutoFilled = ref(false)
let suppressCategoryWatch = false

// ユーザーが手動でカテゴリーを変更したら、自動セット扱いを解除する（以降は上書きしない）
watch(
    () => form.categoryId,
    () => {
        if (!suppressCategoryWatch) {
            categoryAutoFilled.value = false
        }
    },
)

// 店舗選択に応じてデフォルトカテゴリーを反映する（F-1）。
// ユーザーが明示選択したカテゴリーは尊重して上書きせず、自動セット分のみ更新/クリアする。
function applyShopDefaultCategory(defaultCategoryId: string | null) {
    if (form.categoryId && !categoryAutoFilled.value) {
        return
    }

    // 無効化された（選択肢に存在しない）デフォルトカテゴリーは自動セットしない
    const validId
        = defaultCategoryId && props.categories.some((c) => c.id === defaultCategoryId)
            ? defaultCategoryId
            : null

    suppressCategoryWatch = true
    form.categoryId = validId ?? ''
    categoryAutoFilled.value = validId !== null
    void nextTick(() => {
        suppressCategoryWatch = false
    })
}

watch(shopModel, (val) => {
    if (val && typeof val === 'object') {
        form.shopId = val.id
        form.shopName = undefined
        applyShopDefaultCategory(val.defaultCategoryId ?? null)
    } else if (typeof val === 'string' && val.trim() !== '') {
        form.shopId = undefined
        form.shopName = val
    } else {
        form.shopId = undefined
        form.shopName = undefined
    }
})

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success(isEdit.value ? '支出を更新しました' : '支出を登録しました')
            emit('saved', form.expenseDate)
        },
        onError: () => snackbar.error('保存に失敗しました'),
    }

    if (isEdit.value && props.expense) {
        form.patch(route('budget.expenses.update', props.expense.id), options)
    } else {
        form.post(route('budget.expenses.store'), options)
    }
}
</script>

<template>
    <v-form @submit.prevent="submit">
        <v-text-field
            v-model="form.amount"
            label="金額"
            type="number"
            inputmode="numeric"
            prefix="¥"
            variant="outlined"
            density="comfortable"
            :error-messages="form.errors.amount"/>

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
            :items="paymentMethods"
            item-title="name"
            item-value="id"
            label="支払い方法"
            prepend-inner-icon="mdi-credit-card-outline"
            variant="outlined"
            density="comfortable"
            :error-messages="form.errors.paymentMethodId"
            class="mt-2"/>

        <v-combobox
            v-model="shopModel"
            :items="shopCandidates"
            item-title="name"
            label="店名"
            prepend-inner-icon="mdi-storefront-outline"
            variant="outlined"
            density="comfortable"
            return-object
            clearable
            :error-messages="form.errors.shopName"
            class="mt-2"
            @update:search="onShopSearch"/>

        <v-text-field
            v-model="form.expenseDate"
            label="支出日"
            type="date"
            prepend-inner-icon="mdi-calendar"
            variant="outlined"
            density="comfortable"
            :error-messages="form.errors.expenseDate"
            class="mt-2"/>

        <v-select
            v-model="memberKey"
            :items="memberOptions"
            item-title="name"
            item-value="key"
            label="担当者（任意）"
            prepend-inner-icon="mdi-account"
            variant="outlined"
            density="comfortable"
            clearable
            class="mt-2"/>

        <v-textarea
            v-model="form.memo"
            label="メモ（任意）"
            prepend-inner-icon="mdi-note-text-outline"
            variant="outlined"
            density="comfortable"
            rows="2"
            :error-messages="form.errors.memo"
            class="mt-2"/>

        <div class="d-flex justify-end gap-2 mt-2">
            <v-btn
                variant="text"
                @click="emit('cancel')">
                キャンセル
            </v-btn>
            <v-btn
                type="submit"
                color="primary"
                variant="flat"
                :loading="form.processing">
                {{ isEdit ? '更新' : '登録' }}
            </v-btn>
        </div>
    </v-form>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { formatYen } from '@/Utils/currencyFormatter'
import type {
    QuickEntriesPageResult,
    QuickEntryData,
    StoreQuickEntryRequest,
} from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<QuickEntriesPageResult>()
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
const editing = ref<QuickEntryData | null>(null)
const formKey = ref(0)

const dialogTitle = computed(() => (editing.value ? 'クイック入力を編集' : 'クイック入力を追加'))

const form = useInertiaForm<StoreQuickEntryRequest>({
    name: '',
    categoryId: '',
    paymentMethodId: '',
    shopId: undefined,
    defaultAmount: undefined,
})

function openCreate() {
    editing.value = null
    form.reset()
    formKey.value++
    dialog.value = true
}

function openEdit(quickEntry: QuickEntryData) {
    editing.value = quickEntry
    form.name = quickEntry.name
    form.categoryId = quickEntry.categoryId
    form.paymentMethodId = quickEntry.paymentMethodId
    form.shopId = quickEntry.shopId ?? undefined
    form.defaultAmount = quickEntry.defaultAmount ?? undefined
    formKey.value++
    dialog.value = true
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success(editing.value ? 'クイック入力を更新しました' : 'クイック入力を追加しました')
            dialog.value = false
        },
        onError: () => snackbar.error('保存に失敗しました'),
    }

    if (editing.value) {
        form.patch(route('budget.quick-entries.update', editing.value.id), options)
    } else {
        form.post(route('budget.quick-entries.store'), options)
    }
}

async function remove(quickEntry: QuickEntryData) {
    const ok = await confirm({
        title: 'クイック入力を削除しますか？',
        message: 'この操作は取り消せません（過去の支出には影響しません）。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('budget.quick-entries.destroy', quickEntry.id), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('クイック入力を削除しました'),
        onError: () => snackbar.error('削除に失敗しました'),
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
                <div class="d-flex align-center justify-space-between mb-4">
                    <h2 class="text-h6">クイック入力管理</h2>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openCreate">
                        追加
                    </v-btn>
                </div>

                <v-card v-if="props.quickEntries.length">
                    <v-list lines="two">
                        <template
                            v-for="(qe, i) in props.quickEntries"
                            :key="qe.id">
                            <v-divider v-if="i > 0"/>
                            <v-list-item>
                                <template #prepend>
                                    <v-avatar
                                        :color="qe.categoryColor"
                                        size="40">
                                        <v-icon
                                            v-if="qe.categoryIcon"
                                            :icon="`mdi-${qe.categoryIcon}`"
                                            color="white"/>
                                        <span
                                            v-else
                                            class="text-white text-caption">
                                            {{ qe.categoryName.slice(0, 1) }}
                                        </span>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="ml-2">{{ qe.name }}</v-list-item-title>
                                <v-list-item-subtitle class="ml-2">
                                    {{ qe.categoryName }} ・ {{ qe.paymentMethodName }}
                                    <template v-if="qe.shopName"> ・ {{ qe.shopName }}</template>
                                    <template v-if="qe.defaultAmount">
                                        ・ {{ formatYen(qe.defaultAmount) }}
                                    </template>
                                </v-list-item-subtitle>

                                <template #append>
                                    <div class="d-flex align-center">
                                        <span class="text-caption text-medium-emphasis mr-2">
                                            {{ qe.usageCount }} 回
                                        </span>
                                        <v-btn
                                            icon="mdi-pencil"
                                            variant="text"
                                            size="small"
                                            @click="openEdit(qe)"/>
                                        <v-btn
                                            icon="mdi-delete-outline"
                                            variant="text"
                                            size="small"
                                            color="error"
                                            @click="remove(qe)"/>
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
                        icon="mdi-lightning-bolt-outline"
                        size="48"
                        class="mb-2"/>
                    <div class="text-body-1">登録済みのクイック入力はありません</div>
                    <div class="text-body-2 text-medium-emphasis mt-1">
                        よく使う支出を登録すると、支出登録画面からワンタップで入力できます
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
                            label="クイック入力名"
                            placeholder="例: コンビニ・ランチ"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.name"/>

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
                            v-model="form.defaultAmount"
                            label="デフォルト金額（任意）"
                            type="number"
                            inputmode="numeric"
                            prefix="¥"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.defaultAmount"
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

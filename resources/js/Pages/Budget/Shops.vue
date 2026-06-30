<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import type { ShopData, ShopsPageResult, StoreShopRequest } from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<ShopsPageResult>()
const { confirm } = useConfirmDialog()
const snackbar = useSnackbar()

const categoryItems = computed(() =>
    props.value.categories.map((c) => ({ value: c.id, title: c.name })),
)

const categoryNameOf = (id: string | null) =>
    id ? (props.value.categories.find((c) => c.id === id)?.name ?? null) : null

// ----- 登録 / 編集ダイアログ -----
const dialog = ref(false)
const editing = ref<ShopData | null>(null)
const formKey = ref(0)

const dialogTitle = computed(() => (editing.value ? '店舗を編集' : '店舗を追加'))

const form = useInertiaForm<StoreShopRequest>({
    name: '',
    defaultCategoryId: undefined,
})

function openCreate() {
    editing.value = null
    form.reset()
    formKey.value++
    dialog.value = true
}

function openEdit(shop: ShopData) {
    editing.value = shop
    form.name = shop.name
    form.defaultCategoryId = shop.defaultCategoryId ?? undefined
    formKey.value++
    dialog.value = true
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success(editing.value ? '店舗を更新しました' : '店舗を追加しました')
            dialog.value = false
        },
        onError: () => snackbar.error('保存に失敗しました'),
    }

    if (editing.value) {
        form.patch(route('budget.shops.update', editing.value.id), options)
    } else {
        form.post(route('budget.shops.store'), options)
    }
}

async function remove(shop: ShopData) {
    const ok = await confirm({
        title: '店舗を削除しますか？',
        message: 'この店舗を使った過去の支出からは店舗の紐付けが外れます（記録自体は残ります）。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('budget.shops.destroy', shop.id), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('店舗を削除しました'),
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
                    <h2 class="text-h6">店舗管理</h2>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openCreate">
                        追加
                    </v-btn>
                </div>

                <v-card v-if="props.shops.length">
                    <v-list lines="two">
                        <template
                            v-for="(shop, i) in props.shops"
                            :key="shop.id">
                            <v-divider v-if="i > 0"/>
                            <v-list-item>
                                <template #prepend>
                                    <v-avatar
                                        color="grey-lighten-1"
                                        size="32">
                                        <v-icon
                                            icon="mdi-storefront-outline"
                                            color="white"
                                            size="small"/>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="ml-2">{{ shop.name }}</v-list-item-title>
                                <v-list-item-subtitle class="ml-2">
                                    <template v-if="categoryNameOf(shop.defaultCategoryId)">
                                        既定: {{ categoryNameOf(shop.defaultCategoryId) }} ・
                                    </template>
                                    利用 {{ shop.usageCount }} 回
                                </v-list-item-subtitle>

                                <template #append>
                                    <v-btn
                                        icon="mdi-pencil"
                                        variant="text"
                                        size="small"
                                        @click="openEdit(shop)"/>
                                    <v-btn
                                        icon="mdi-delete-outline"
                                        variant="text"
                                        size="small"
                                        color="error"
                                        @click="remove(shop)"/>
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
                        icon="mdi-storefront-outline"
                        size="48"
                        class="mb-2"/>
                    <div class="text-body-1">登録済みの店舗はありません</div>
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
                            label="店舗名"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.name"/>

                        <v-select
                            v-model="form.defaultCategoryId"
                            :items="categoryItems"
                            label="デフォルトカテゴリー（任意）"
                            variant="outlined"
                            density="comfortable"
                            clearable
                            :error-messages="form.errors.defaultCategoryId"
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

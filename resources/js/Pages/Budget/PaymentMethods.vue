<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import type {
    PaymentMethodData,
    PaymentMethodsPageResult,
    StorePaymentMethodRequest,
} from '@/Types/dto.generated'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<PaymentMethodsPageResult>()
const { confirm } = useConfirmDialog()
const snackbar = useSnackbar()

// ----- 登録 / 編集ダイアログ -----
const dialog = ref(false)
const editing = ref<PaymentMethodData | null>(null)
const formKey = ref(0)

const dialogTitle = computed(() => (editing.value ? '支払い方法を編集' : '支払い方法を追加'))

const form = useInertiaForm<StorePaymentMethodRequest>({
    name: '',
    icon: undefined,
})

function openCreate() {
    editing.value = null
    form.reset()
    formKey.value++
    dialog.value = true
}

function openEdit(paymentMethod: PaymentMethodData) {
    editing.value = paymentMethod
    form.name = paymentMethod.name
    form.icon = paymentMethod.icon ?? undefined
    formKey.value++
    dialog.value = true
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success(editing.value ? '支払い方法を更新しました' : '支払い方法を追加しました')
            dialog.value = false
        },
        onError: () => snackbar.error('保存に失敗しました'),
    }

    if (editing.value) {
        form.patch(route('budget.payment-methods.update', editing.value.id), options)
    } else {
        form.post(route('budget.payment-methods.store'), options)
    }
}

async function remove(paymentMethod: PaymentMethodData) {
    const ok = await confirm({
        title: '支払い方法を削除しますか？',
        message: 'この支払い方法を使った過去の支出は残ります（記録には支払い方法名が保持されます）。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('budget.payment-methods.destroy', paymentMethod.id), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('支払い方法を削除しました'),
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
                    <h2 class="text-h6">支払い方法管理</h2>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="openCreate">
                        追加
                    </v-btn>
                </div>

                <v-card>
                    <v-list lines="one">
                        <template
                            v-for="(pm, i) in props.paymentMethods"
                            :key="pm.id">
                            <v-divider v-if="i > 0"/>
                            <v-list-item>
                                <template #prepend>
                                    <v-avatar
                                        color="grey-lighten-1"
                                        size="32">
                                        <v-icon
                                            :icon="pm.icon ? `mdi-${pm.icon}` : 'mdi-credit-card-outline'"
                                            color="white"
                                            size="small"/>
                                    </v-avatar>
                                </template>

                                <v-list-item-title class="ml-2">
                                    {{ pm.name }}
                                    <v-chip
                                        v-if="pm.isSystem"
                                        size="x-small"
                                        class="ml-2">
                                        既定
                                    </v-chip>
                                </v-list-item-title>

                                <template #append>
                                    <template v-if="!pm.isSystem">
                                        <v-btn
                                            icon="mdi-pencil"
                                            variant="text"
                                            size="small"
                                            @click="openEdit(pm)"/>
                                        <v-btn
                                            icon="mdi-delete-outline"
                                            variant="text"
                                            size="small"
                                            color="error"
                                            @click="remove(pm)"/>
                                    </template>
                                    <v-icon
                                        v-else
                                        icon="mdi-lock-outline"
                                        size="small"
                                        color="grey"/>
                                </template>
                            </v-list-item>
                        </template>
                    </v-list>
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
                            label="支払い方法名"
                            variant="outlined"
                            density="comfortable"
                            :error-messages="form.errors.name"/>

                        <v-text-field
                            v-model="form.icon"
                            label="アイコン（mdi 名・任意）"
                            placeholder="例: cash, credit-card, bank"
                            variant="outlined"
                            density="comfortable"
                            :prepend-inner-icon="form.icon ? `mdi-${form.icon}` : 'mdi-credit-card-outline'"
                            :error-messages="form.errors.icon"
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

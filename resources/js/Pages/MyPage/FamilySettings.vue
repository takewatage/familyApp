<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { router } from '@inertiajs/vue3'
import type { FamilySettingsResult, UpdateFamilySettingsRequest } from '@/Types/dto.generated'
import RegenerateFamilyCodeDialog from '@/Components/Family/RegenerateFamilyCodeDialog.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<FamilySettingsResult>()
const { open } = useDialogService()
const snackbar = useSnackbar()

const form = useInertiaForm<UpdateFamilySettingsRequest>({
    name: props.value.family.name,
    maxMembers: props.value.family.maxMembers,
})

function handleSubmit() {
    form.patch(route('family.settings.update'), {
        only: ['family'],
        onSuccess: () => snackbar.success('家族設定を更新しました'),
        onError: () => snackbar.error('更新に失敗しました'),
    })
}

function openRegenerateDialog() {
    open({
        component: RegenerateFamilyCodeDialog,
        toolbar: { title: '家族コード再生成' },
        maxWidth: 400,
    })
}

function formatExpiry(expiresAt: string | null | undefined): string {
    if (!expiresAt) {
        return '無期限'
    }

    return new Date(expiresAt).toLocaleString('ja-JP')
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="8"
                md="6">
                <div class="d-flex align-center mb-4">
                    <v-btn
                        icon="mdi-arrow-left"
                        variant="text"
                        @click="router.visit(route('mypage.index'))"/>
                    <span class="text-h6 ml-2">家族設定変更</span>
                </div>

                <v-card>
                    <v-card-text>
                        <v-text-field
                            v-model="form.name"
                            label="家族名"
                            prepend-inner-icon="mdi-home-heart"
                            variant="outlined"
                            density="comfortable"
                            :readonly="!props.isOwner"
                            :error-messages="form.errors.name"/>

                        <v-text-field
                            v-model.number="form.maxMembers"
                            label="最大メンバー数"
                            prepend-inner-icon="mdi-account-group"
                            type="number"
                            variant="outlined"
                            density="comfortable"
                            :readonly="!props.isOwner"
                            :error-messages="form.errors.maxMembers"
                            class="mt-2"/>
                    </v-card-text>
                    <v-card-actions v-if="props.isOwner">
                        <v-spacer/>
                        <v-btn
                            color="primary"
                            variant="flat"
                            :loading="form.processing"
                            @click="handleSubmit">
                            保存
                        </v-btn>
                    </v-card-actions>
                </v-card>

                <v-card class="mt-4">
                    <v-card-title>招待コード</v-card-title>
                    <v-card-text>
                        <div class="d-flex align-center gap-2 mb-2">
                            <span class="text-h6 font-weight-bold">{{ props.family.code }}</span>
                            <v-btn
                                icon="mdi-content-copy"
                                size="small"
                                variant="text"
                                @click="navigator.clipboard.writeText(props.family.code)"/>
                        </div>
                        <p class="text-body-2 text-medium-emphasis">
                            有効期限: {{ formatExpiry(props.family.codeExpiresAt) }}
                        </p>
                    </v-card-text>
                    <v-card-actions v-if="props.isOwner">
                        <v-spacer/>
                        <v-btn
                            color="warning"
                            variant="tonal"
                            prepend-icon="mdi-refresh"
                            @click="openRegenerateDialog">
                            コードを再生成
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

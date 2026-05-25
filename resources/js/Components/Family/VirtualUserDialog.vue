<script setup lang="ts">
import ImageUploadField from '@/Components/Common/ImageUploadField.vue'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import type { DialogComponentProps } from '@/Composables/Common/useDialogService'
import type { SaveVirtualUserRequest, VirtualUserData } from '@/Types/dto.generated'

type Props = {
    virtualUser?: VirtualUserData | null
} & DialogComponentProps

const props = defineProps<Props>()
const snackbar = useSnackbar()

const isEdit = !!props.virtualUser

const form = useInertiaForm<SaveVirtualUserRequest>({
    name: props.virtualUser?.name ?? '',
    avatarImage: undefined,
})

function handleSubmit() {
    if (isEdit) {
        // PHP は PATCH のマルチパートボディを解析しないため POST + _method スプーフィングで送信
        form.post(route('family.virtual-users.update', { virtualUser: props.virtualUser!.id }), {
            preserveScroll: true,
            only: ['virtualUsers'],
            transform: (data: any) => ({ ...data, _method: 'PATCH' }),
            onSuccess: () => {
                snackbar.success('仮想メンバーを更新しました')
                props.onClose()
            },
        })
    } else {
        form.post(route('family.virtual-users.store'), {
            preserveScroll: true,
            only: ['virtualUsers'],
            onSuccess: () => {
                snackbar.success('仮想メンバーを追加しました')
                props.onClose()
            },
        })
    }
}
</script>

<template>
    <v-card flat>
        <v-card-text>
            <div class="d-flex justify-center mb-4">
                <ImageUploadField
                    v-model="form.avatarImage"
                    :current-url="props.virtualUser?.avatar?.url"
                    preview-variant="avatar"
                />
            </div>
            <v-text-field
                v-model="form.name"
                label="名前"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                density="comfortable"
                :error-messages="form.errors.name"/>
        </v-card-text>
        <v-card-actions>
            <v-btn
                variant="text"
                @click="props.onClose()">
                キャンセル
            </v-btn>
            <v-spacer/>
            <v-btn
                color="primary"
                variant="flat"
                :loading="form.processing"
                @click="handleSubmit">
                {{ isEdit ? '更新' : '追加' }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

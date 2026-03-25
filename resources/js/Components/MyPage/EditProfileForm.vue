<script setup lang="ts">
import ImageUploadField from '@/Components/Common/ImageUploadField.vue'
import { FileData, MyPageData, SaveProfileData } from '@/Types/dto.generated'
import type { DialogComponentProps } from '@/Composables/Common/useDialogService'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import DatePickerDialog from "@/Components/Common/DatePickerDialog.vue";

type Props = {
    name: string
    birthday: string | null
    avatar: FileData | null
} & DialogComponentProps<MyPageData>

const props = defineProps<Props>()

const form = useInertiaForm<SaveProfileData>({
    name: props.name ?? '',
    birthday: props.birthday ?? null,
    avatarImage: undefined,
})

function handleSubmit() {
    form.post(route('mypage.update'), {
        only: ['user'],
        onSuccess: () => props.onClose(),
    })
}
</script>

<template>
    <v-card flat>
        <v-card-text>
            <div class="d-flex justify-center mb-4">
                <ImageUploadField
                    v-model="form.avatarImage"
                    :current-url="props.avatar?.url"/>
            </div>
            <v-text-field
                v-model="form.name"
                label="ユーザー名"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                density="comfortable"
                :error-messages="form.errors.name"/>

            <DatePickerDialog
                v-model="form.birthday"
                label="生年月日"
                placeholder="例: 2000年1月1日"
                clearable
                :error-messages="form.errors.birthday"
                hint="生年月日を選択してください"/>
        </v-card-text>
        <v-card-actions>
            <v-btn
                color="primary"
                variant="flat"
                :loading="form.processing"
                block
                @click="handleSubmit">
                保存
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

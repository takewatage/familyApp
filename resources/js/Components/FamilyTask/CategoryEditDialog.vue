<script setup lang="ts">
import { computed, ref } from 'vue'
import { TaskCategoryData, UpdateTaskCategoryRequest } from '@/Types/dto.generated'
import { DialogComponentProps } from '@/Composables/Common/useDialogService'
import { taskCategoryApi } from '@/Api/taskCategoryApi'

type Props = {
    category?: TaskCategoryData
    onSaved: (category: TaskCategoryData) => void
} & DialogComponentProps<void>

const props = defineProps<Props>()

const isEditMode = computed(() => !!props.category)
const name = ref(props.category?.name ?? '')
const isSubmitting = ref(false)

const save = async () => {
    if (!name.value.trim()) return
    isSubmitting.value = true
    try {
        if (isEditMode.value) {
            const params: UpdateTaskCategoryRequest = { name: name.value }
            const response = await taskCategoryApi.update(props.category!.id, params)
            props.onSaved(response.data.category)
        } else {
            const response = await taskCategoryApi.store({ name: name.value })
            props.onSaved(response.data.category)
        }
        props.onClose()
    } catch (error) {
        console.error('Failed to save category:', error)
    } finally {
        isSubmitting.value = false
    }
}
</script>

<template>
    <v-card>
        <v-toolbar color="surface" density="comfortable">
            <v-toolbar-title>{{ isEditMode ? 'カテゴリーを編集' : 'カテゴリーを追加' }}</v-toolbar-title>
        </v-toolbar>
        <v-card-text class="pt-4">
            <v-text-field
                v-model="name"
                label="カテゴリー名"
                variant="outlined"
                autofocus
                hide-details
            />
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
            <v-spacer/>
            <v-btn variant="text" @click="onClose()">キャンセル</v-btn>
            <v-btn
                color="primary"
                variant="flat"
                :loading="isSubmitting"
                :disabled="!name.trim()"
                @click="save">
                {{ isEditMode ? '保存' : '追加' }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { TaskCategoryData, TaskData } from '@/Types/dto.generated'
import { familyTaskApi } from '@/Api/familyTaskApi'
import ImageUploadField from '@/Components/Common/ImageUploadField.vue'
import ColorChipSelect from '@/Components/Common/ColorChipSelect.vue'

type Props = {
    categories: TaskCategoryData[]
    selectedCategory: string
    editMode?: boolean
    task?: TaskData | null
    onClose?: (result?: TaskData) => void
}

type Emits = {
    (e: 'task-created', task: TaskData): void
    (e: 'task-updated', task: TaskData): void
}

const props = withDefaults(defineProps<Props>(), {
    editMode: false,
    task: null,
    onClose: undefined,
})

const emit = defineEmits<Emits>()

const isSubmitting = ref(false)
const showPhotoField = ref(false)
const showMemoField = ref(false)
const photoFile = ref<File | null>(null)
const taskForm = ref({
    content: '',
    categoryId: props.selectedCategory,
    color: 'white',
    memo: '',
})

// 編集モードの場合、初期値を設定
if (props.editMode && props.task) {
    taskForm.value = {
        content: props.task.content,
        categoryId: props.task.categoryId,
        color: props.task.color,
        memo: props.task.memo || '',
    }
}

const closeSheet = () => {
    if (props.onClose) {
        props.onClose()
    }
}

const handleSubmitTask = async () => {
    if (!taskForm.value.content.trim() || !taskForm.value.categoryId) {
        return
    }

    isSubmitting.value = true

    try {
        if (props.editMode && props.task) {
            // 編集モード: 更新API
            const response = await familyTaskApi.update(props.task.id, {
                content: taskForm.value.content,
                category_id: taskForm.value.categoryId,
                color: taskForm.value.color,
                memo: taskForm.value.memo,
            })
            const updatedTask = response.data.task
            emit('task-updated', updatedTask)
            if (props.onClose) {
                props.onClose(updatedTask)
            }
        } else {
            // 新規作成モード
            const response = await familyTaskApi.store({
                content: taskForm.value.content,
                category_id: taskForm.value.categoryId,
                color: taskForm.value.color,
                memo: taskForm.value.memo,
            })
            const newTask = response.data.task
            emit('task-created', newTask)
            closeSheet()
        }
    } catch (error) {
        console.error('Failed to save task:', error)
    } finally {
        isSubmitting.value = false
    }
}

const categoryName = computed(() => {
    return props.categories.find((c) => c.id == props.selectedCategory)?.name
})
</script>

<template>
    <v-card
        class="pa-4"
        :elevation="editMode ? 0 : 3">
        <v-card-title
            v-if="!editMode"
            class="text-h6">
            タスクを追加
        </v-card-title>
        <v-chip
            class="chip"
            color="primary">
            {{ categoryName }}
        </v-chip>
        <v-card-text class="px-0">
            <v-textarea
                v-model="taskForm.content"
                label="タスク内容"
                placeholder="やることを入力..."
                variant="outlined"
                rows="1"
                autofocus
                clearable
                auto-grow
                color="primary"></v-textarea>

            <ColorChipSelect
                v-model="taskForm.color"
                class="mb-2" />

            <template v-if="!editMode">
                <div class="field-toggle-btns">
                    <div
                        class="field-toggle-btn"
                        :class="{ active: showPhotoField }"
                        @click="showPhotoField = !showPhotoField">
                        <v-icon size="small">mdi-camera</v-icon>
                        写真
                    </div>
                    <div
                        class="field-toggle-btn"
                        :class="{ active: showMemoField }"
                        @click="showMemoField = !showMemoField">
                        <v-icon size="small">mdi-note-text</v-icon>
                        メモ
                    </div>
                </div>
                <!-- 写真フィールド -->
                <div
                    v-if="showPhotoField"
                    class="optional-field">
                    <ImageUploadField v-model="photoFile" />
                </div>

                <!-- メモフィールド -->
                <div
                    v-if="showMemoField"
                    class="optional-field">
                    <v-textarea
                        v-model="taskForm.memo"
                        label="メモ"
                        variant="outlined"
                        density="comfortable"
                        rows="3"
                        auto-grow
                        placeholder="詳細やメモを入力..."
                        color="primary"></v-textarea>
                </div>
            </template>
            <template v-else>
                <v-divider></v-divider>
                <v-list-item class="pa-0 py-3">
                    <template #prepend>
                        <v-avatar>
                            <v-icon color="primary">mdi-camera-plus</v-icon>
                        </v-avatar>
                    </template>
                    <ImageUploadField v-model="photoFile" />
                </v-list-item>
                <v-divider></v-divider>
                <v-list-item class="pa-0">
                    <template #prepend>
                        <v-avatar>
                            <v-icon color="primary">mdi-note-text</v-icon>
                        </v-avatar>
                    </template>
                    <v-textarea
                        v-model="taskForm.memo"
                        class="py-5"
                        label="メモ"
                        variant="outlined"
                        density="comfortable"
                        rows="4"
                        auto-grow
                        hide-details
                        placeholder="詳細やメモを入力..."
                        color="primary"></v-textarea>
                </v-list-item>
            </template>
        </v-card-text>
        <v-card-actions class="justify-end">
            <v-btn
                v-if="!editMode"
                variant="text"
                @click="closeSheet">
                キャンセル
            </v-btn>
            <v-btn
                color="primary"
                variant="flat"
                :width="editMode ? '100%' : 'auto'"
                :loading="isSubmitting"
                :disabled="!taskForm.content.trim() || !taskForm.categoryId"
                size="large"
                @click="handleSubmitTask">
                {{ editMode ? '更新' : '追加' }}
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<style scoped lang="scss">
.chip {
    width: fit-content;
}

/* 追加入力フィールド */
.optional-field {
    border-radius: 12px;
    padding: 16px;
    margin-top: 12px;
}

.field-toggle-btns {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}

.field-toggle-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    transition: all 0.2s;
}

.field-toggle-btn.active {
    border-color: rgb(var(--v-theme-primary));
    color: rgb(var(--v-theme-primary));
}
</style>

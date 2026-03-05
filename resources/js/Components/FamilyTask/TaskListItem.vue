<script setup lang="ts">
import { TaskData } from '@/Types/dto.generated'

defineProps<{
    task: TaskData
    showEdit?: boolean
}>()

const emit = defineEmits<{
    (e: 'toggle'): void
    (e: 'edit'): void
}>()
</script>

<template>
    <v-list-item
        v-ripple="{ class: 'text-primary' }"
        min-height="auto"
        class="task-list-item rounded-lg pa-3 mb-3"
        :class="[{ 'line-through': task.isCompleted }, `bg-${task.color ?? 'white'}`]"
        :elevation="showEdit ? 4 : 0"
        @click="emit('toggle')">
        <template #title>
            <div class="title">{{ task.content }}</div>
        </template>
        <template #subtitle>
            <div class="pt-1 memo">{{ task.memo }}</div>
        </template>
        <template #prepend>
            <v-list-item-action start>
                <v-checkbox-btn
                    color="primary"
                    :model-value="task.isCompleted"/>
            </v-list-item-action>
        </template>
        <template
            v-if="showEdit"
            #append>
            <v-btn
                density="comfortable"
                icon="mdi-pencil"
                @click.stop="emit('edit')"/>
        </template>
    </v-list-item>
</template>

<style scoped lang="scss">
.task-list-item {
    .title,
    .memo {
        white-space: pre-wrap;
    }

    :deep(.v-list-item__prepend),
    :deep(.v-list-item__append) {
        padding-top: 0 !important;
    }

    &.line-through {
        :deep(.v-list-item__content) {
            text-decoration: line-through;
        }
    }
}
</style>

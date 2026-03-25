<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps<{
    modelValue?: string | null
    label?: string
    placeholder?: string
    required?: boolean
    errorMessages?: string | string[]
    hint?: string
    clearable?: boolean
    density?: 'default' | 'comfortable' | 'compact'
    variant?: 'outlined' | 'filled' | 'underlined' | 'plain' | 'solo'
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void
}>()

const dialog = ref(false)
const pickerDate = ref<Date | null>(null)
let isInitializing = false

const toDate = (dateStr: string | null | undefined): Date | null => {
    if (!dateStr) {
        return null
    }

    const d = new Date(dateStr + 'T00:00:00')
    return isNaN(d.getTime()) ? null : d
}

const toDateStr = (date: Date | null): string | null => {
    if (!date) {
        return null
    }

    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

const displayDate = computed(() => {
    if (!props.modelValue) {
        return ''
    }

    const parts = props.modelValue.split('-')
    if (parts.length !== 3) {
        return props.modelValue
    }

    return `${parts[0]}年${Number(parts[1])}月${Number(parts[2])}日`
})

const openDialog = () => {
    isInitializing = true
    pickerDate.value = toDate(props.modelValue)
    dialog.value = true
    nextTick(() => {
        isInitializing = false
    })
}

watch(pickerDate, (newDate) => {
    if (isInitializing) {
        return
    }

    emit('update:modelValue', toDateStr(newDate))
    nextTick(() => {
        dialog.value = false
    })
})

const cancel = () => {
    dialog.value = false
}

const clear = () => {
    isInitializing = true
    pickerDate.value = null
    emit('update:modelValue', null)
    dialog.value = false
    nextTick(() => {
        isInitializing = false
    })
}
</script>

<template>
    <v-text-field
        :model-value="displayDate"
        :label="label"
        :placeholder="placeholder"
        :required="required"
        :error-messages="errorMessages"
        :hint="hint"
        :density="density ?? 'comfortable'"
        :variant="variant ?? 'outlined'"
        readonly
        prepend-inner-icon="mdi-calendar"
        @click="openDialog"
        @click:prepend-inner="openDialog" />

    <v-dialog
        v-model="dialog"
        max-width="360">
        <v-card>
            <v-date-picker
                v-model="pickerDate"
                locale="ja"
                color="primary"
                show-adjacent-months
                hide-header />
            <v-card-actions>
                <v-btn
                    v-if="clearable"
                    variant="text"
                    color="error"
                    @click="clear">
                    クリア
                </v-btn>
                <v-spacer />
                <v-btn
                    variant="text"
                    @click="cancel">
                    キャンセル
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

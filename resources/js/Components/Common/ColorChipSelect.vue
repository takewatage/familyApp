<script setup lang="ts">
const props = defineProps<{
    modelValue: string
    colors?: string[]
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const defaultColors = ['white', 'red', 'pink', 'purple', 'indigo', 'blue', 'cyan', 'teal', 'green', 'lime', 'amber', 'orange']

const colorList = props.colors ?? defaultColors
</script>

<template>
    <div class="color-chip-select">
        <v-chip
            v-for="color in colorList"
            :key="color"
            :color="color"
            size="small"
            class="color-chip"
            :class="{ selected: modelValue === color, 'white-chip': color === 'white' }"
            :variant="modelValue === color ? 'flat' : 'tonal'"
            @click="emit('update:modelValue', color)">
            <v-icon
                v-if="modelValue === color"
                size="x-small">
                mdi-check
            </v-icon>
        </v-chip>
    </div>
</template>

<style scoped lang="scss">
.color-chip-select {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.color-chip {
    transition: box-shadow 0.15s ease;

    &.selected {
        box-shadow:
            0 0 0 2.5px rgb(var(--v-theme-surface)),
            0 0 0 4.5px rgba(0, 0, 0, 0.4);
    }

    &.white-chip {
        border: 1.5px solid #ddd;
    }
}
</style>

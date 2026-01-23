<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'
import type { VBtn } from 'vuetify/components'
import { ButtonOption } from '@/types/dok'

interface Props {
    buttons: ButtonOption[]
    columns?: number
    rows?: number
}

withDefaults(defineProps<Props>(), {
    columns: 4,
    rows: 5,
})

const emit = defineEmits<{
    buttonClick: [button: ButtonOption]
}>()

const handleButtonClick = (button: ButtonOption) => {
    emit('buttonClick', button)
}
</script>

<template>
    <div
        class="calculator-grid"
        :style="{
            gridTemplateColumns: `repeat(${columns}, 1fr)`,
            gridTemplateRows: `repeat(${rows}, 1fr)`,
        }">
        <div
            v-for="(button, index) in buttons"
            :key="index"
            class="grid-item"
            :style="{
                gridRow: button.rowSpan ? `span ${button.rowSpan}` : 'span 1',
                gridColumn: button.colSpan ? `span ${button.colSpan}` : 'span 1',
            }">
            <v-btn
                tabindex="-1"
                :variant="button.variant ?? 'outlined'"
                :color="button.color || 'primary'"
                class="calculator-button"
                :class="{ 'text-white': button.isDark }"
                elevation="2"
                @click="handleButtonClick(button)">
                <v-icon v-if="button.icon">{{ button.icon }}</v-icon>
                <span v-else>{{ button.label }}</span>
            </v-btn>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.calculator-grid {
    display: grid;
    gap: 8px;
    height: 100%;
    width: 100%;
    box-sizing: border-box;
}

.grid-item {
    display: flex;
    width: 100%;
    height: 100%;
    position: relative;
}

.calculator-button {
    flex: 1;
    width: 100% !important;
    height: 100% !important;
    min-width: 0 !important;
    min-height: 0 !important;
    margin: 0 !important;
    font-size: 1.4rem !important;
    font-weight: 500;

    &:hover {
        transform: scale(1.02);
        transition: transform 0.1s ease;
    }

    &:active {
        transform: scale(0.98);
    }

    .v-btn__overlay {
        opacity: 0.05;
    }

    &:focus-visible {
        outline: 2px solid rgba(var(--v-theme-primary), 0.3);
        outline-offset: -2px;
    }

    &.text-white {
        color: white !important;
    }
}
</style>

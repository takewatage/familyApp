import type { VBtn } from 'vuetify/components'

export type ButtonOption = {
    label?: string
    icon?: string
    color?: string
    action?: () => void
    rowSpan?: number
    colSpan?: number
    variant?: VBtn['variant']
    isDark?: boolean
}

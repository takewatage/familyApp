import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTheme } from 'vuetify'

export const THEME_COLORS: Record<string, string> = {
    pink: '#ff45ce',
    blue: '#2196F3',
    purple: '#9C27B0',
    green: '#4CAF50',
    orange: '#FF9800',
    teal: '#009688',
}

export const THEME_COLOR_LABELS: Record<string, string> = {
    pink: 'ピンク',
    blue: 'ブルー',
    purple: 'パープル',
    green: 'グリーン',
    orange: 'オレンジ',
    teal: 'ティール',
}

type UserSettings = {
    theme: string
    themeColor: string
}

export function useAppTheme() {
    const theme = useTheme()
    const page = usePage()

    function applyTheme(settings: UserSettings) {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        const isDark =
            settings.theme === 'dark' || (settings.theme === 'system' && prefersDark)

        theme.global.name.value = isDark ? 'darkTheme' : 'lightTheme'

        const primary = THEME_COLORS[settings.themeColor] ?? THEME_COLORS.pink
        theme.themes.value['lightTheme'].colors.primary = primary
        theme.themes.value['darkTheme'].colors.primary = primary
    }

    watch(
        () => (page.props as any).userSettings as UserSettings | undefined,
        (settings) => {
            if (settings) {
                applyTheme(settings)
            }
        },
        { immediate: true, deep: true },
    )

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    mediaQuery.addEventListener('change', () => {
        const settings = (page.props as any).userSettings as UserSettings | undefined
        if (settings) {
            applyTheme(settings)
        }
    })
}

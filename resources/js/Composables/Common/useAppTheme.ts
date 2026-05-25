import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTheme } from 'vuetify'

export type ThemePreset = {
    key: string
    label: string
    primary: string
    secondary: string
}

export const THEMES: ThemePreset[] = [
    { key: 'pink',      label: 'ピンク',       primary: '#FF45CE', secondary: '#2DD4AA' },
    { key: 'sunset',    label: 'サンセット',   primary: '#F06292', secondary: '#FFB74D' },
    { key: 'ocean',     label: 'オーシャン',   primary: '#0288D1', secondary: '#4DB6AC' },
    { key: 'forest',    label: 'フォレスト',   primary: '#388E3C', secondary: '#FFA726' },
    { key: 'lavender',  label: 'ラベンダー',   primary: '#7B1FA2', secondary: '#80DEEA' },
    { key: 'autumn',    label: 'オータム',     primary: '#BF360C', secondary: '#FFB300' },
    { key: 'midnight',  label: 'ミッドナイト', primary: '#283593', secondary: '#7E57C2' },
]

export const DEFAULT_THEME_KEY = 'pink'

type UserSettings = {
    theme: string
    themeName: string
}

export function useAppTheme() {
    const theme = useTheme()
    const page = usePage()

    function applyTheme(settings: UserSettings) {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        const isDark =
            settings.theme === 'dark' || (settings.theme === 'system' && prefersDark)

        theme.global.name.value = isDark ? 'darkTheme' : 'lightTheme'

        const preset = THEMES.find((t) => t.key === settings.themeName) ?? THEMES[0]
        theme.themes.value['lightTheme'].colors.primary   = preset.primary
        theme.themes.value['darkTheme'].colors.primary    = preset.primary
        theme.themes.value['lightTheme'].colors.secondary = preset.secondary
        theme.themes.value['darkTheme'].colors.secondary  = preset.secondary
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

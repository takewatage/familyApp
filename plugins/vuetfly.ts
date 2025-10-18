import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'

const pinkTheme = {
    dark: false,
    colors: {
        background: 'hsl(48 33.3% 97.1% / 1)',
        surface: '#FFFFFF',
        'surface-bright': '#FFFFFF',
        'surface-light': '#EEEEEE',
        'surface-variant': '#424242',
        'on-surface-variant': '#EEEEEE',
        primary: '#ff45ce',
        secondary: '#2dd4aa',
        'secondary-darken-1': '#018786',
        error: '#B00020',
        info: '#2196F3',
        success: '#4CAF50',
        warning: '#FB8C00',
    },
}
export const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'pinkTheme',
        themes: {
            pinkTheme,
        },
    },
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        },
    },
    display: {
        mobileBreakpoint: 'sm',
        thresholds: {
            xs: 0,
            sm: 340,
            md: 540,
            lg: 768,
            xl: 1080,
            xxl: 1280,
        },
    },
})

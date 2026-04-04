import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import { ja } from 'vuetify/locale'

const lightTheme = {
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

const darkTheme = {
    dark: true,
    colors: {
        background: '#121212',
        surface: '#1E1E1E',
        'surface-bright': '#2C2C2C',
        'surface-light': '#2C2C2C',
        'surface-variant': '#BDBDBD',
        'on-surface-variant': '#212121',
        primary: '#ff45ce',
        secondary: '#2dd4aa',
        'secondary-darken-1': '#018786',
        error: '#CF6679',
        info: '#64B5F6',
        success: '#81C784',
        warning: '#FFB74D',
    },
}

export const vuetify = createVuetify({
    components,
    directives,
    locale: {
        locale: 'ja',
        messages: { ja },
    },
    theme: {
        defaultTheme: 'lightTheme',
        themes: {
            lightTheme,
            darkTheme,
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

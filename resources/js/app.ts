import './bootstrap'
import axios from 'axios'
import { createApp, DefineComponent, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import '../sass/app.scss'
import { vuetify } from '@/Plugins/vuetifly'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { keysToCamel, keysToSnake } from '@/Utils/caseConverter'
import { setupDialogPlugin } from '@/Composables/Common/useDialogService'
import { setupOverlayPlugin } from '@/Composables/Common/useOverlayService'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue'))
        page.then((module) => {
            module.default.layout = module.default.layout || GuestLayout
            return module
        })
        return page
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.config.globalProperties.route = route
        // プラグインの設定
        app.use(plugin)
        app.use(ZiggyVue, Ziggy)
        app.use(vuetify)

        // ダイアログプラグインのセットアップ
        setupDialogPlugin(app, vuetify)
        setupOverlayPlugin(app, vuetify)
        app.mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})

// リクエスト: camelCase → snake_case
axios.interceptors.request.use((config) => {
    if (config.data && typeof config.data === 'object') {
        config.data = keysToSnake(config.data)
    }
    if (config.params && typeof config.params === 'object') {
        config.params = keysToSnake(config.params)
    }
    return config
})

// レスポンス: snake_case → camelCase
axios.interceptors.response.use(
    (response) => {
        if (response.data && typeof response.data === 'object') {
            response.data = keysToCamel(response.data)
        }
        return response
    },
    (error) => {
        if (error.response?.data && typeof error.response.data === 'object') {
            error.response.data = keysToCamel(error.response.data)
        }
        return Promise.reject(error)
    },
)

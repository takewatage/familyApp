import './bootstrap'
import axios from 'axios'
import { createApp, DefineComponent, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
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
import { useLoading } from '@/Composables/Common/useLoading'

// AxiosRequestConfig にカスタムプロパティを追加
declare module 'axios' {
    interface AxiosRequestConfig {
        /** true(デフォルト): ローディング表示 / false: 非表示 */
        showLoading?: boolean
    }
}

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

const { start: loadingStart, end: loadingEnd } = useLoading()

// リクエスト: camelCase → snake_case + ローディング制御
axios.interceptors.request.use((config) => {
    // showLoading が false でなければローディング表示（デフォルト: true）
    if (config.showLoading !== false) {
        loadingStart()
        config.showLoading = true
    }

    if (config.data && typeof config.data === 'object') {
        config.data = keysToSnake(config.data)
    }
    if (config.params && typeof config.params === 'object') {
        config.params = keysToSnake(config.params)
    }
    return config
})

// レスポンス: snake_case → camelCase + ローディング制御
axios.interceptors.response.use(
    (response) => {
        if (response.config.showLoading !== false) loadingEnd()

        if (response.data && typeof response.data === 'object') {
            response.data = keysToCamel(response.data)
        }
        return response
    },
    (error) => {
        if (error.config?.showLoading !== false) loadingEnd()

        if (error.response?.data && typeof error.response.data === 'object') {
            error.response.data = keysToCamel(error.response.data)
        }
        return Promise.reject(error)
    },
)

import './bootstrap'

import { createApp, h, DefineComponent } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import '../sass/app.scss'
import { vuetify } from '@/Plugins/vuetifly'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { keysToSnake, keysToCamel } from '@/Utils/caseConverter'
// import { setupDialogPlugin } from '@/composables/common/useDialogService'
// import { setupOverlayPlugin } from '@/composables/common/useOverlayService'

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
        // setupDialogPlugin(app, vuetify)
        // setupOverlayPlugin(app, vuetify)
        app.mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})

// Inertiaイベントリスナー: リクエスト送信前に camelCase → snake_case に変換
router.on('before', (event) => {
    const visit = event.detail.visit

    // POSTなどのデータ送信時
    if (visit.data && typeof visit.data === 'object') {
        visit.data = keysToSnake(visit.data)
    }

    // GETのクエリパラメータ
    if (visit.params && typeof visit.params === 'object') {
        visit.params = keysToSnake(visit.params)
    }
})

// Inertiaイベントリスナー: レスポンス受信後に snake_case → camelCase に変換
router.on('success', (event) => {
    const page = event.detail.page
    if (page.props && typeof page.props === 'object') {
        page.props = keysToCamel(page.props)
    }
})

// Inertiaイベントリスナー: エラーレスポンスのエラーメッセージも snake_case → camelCase に変換
router.on('error', (event) => {
    const errors = event.detail.errors
    if (errors && typeof errors === 'object') {
        // エラーオブジェクトのキーをcamelCaseに変換
        const camelErrors = keysToCamel(errors)
        // 元のerrorsオブジェクトを置き換え
        event.detail.errors = camelErrors
    }
})

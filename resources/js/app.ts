import './bootstrap'

import { createApp, h, DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import '../sass/app.scss'
import { vuetify } from '@/Plugins/vuetifly'
import GuestLayout from '@/Layouts/GuestLayout.vue'
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

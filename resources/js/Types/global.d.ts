import { PageProps as InertiaPageProps } from '@inertiajs/core'
import { AxiosInstance } from 'axios'
import ziggyRoute, { Config as ZiggyConfig } from 'ziggy-js'
import { PageProps as AppPageProps } from './'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
    interface Window {
        axios: AxiosInstance
        Echo: Echo
        Pusher: typeof Pusher
    }

    let route: typeof ziggyRoute

    let Ziggy: ZiggyConfig
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {
    }
}

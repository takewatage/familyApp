import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '@inertiajs/core'

export const usePageProps = <T extends Record<string, any>>() => {
    const page = usePage<PageProps & T>()
    return page.props as T
}

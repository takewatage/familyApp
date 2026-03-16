import { usePage } from '@inertiajs/vue3'
import type { PageProps } from '@inertiajs/core'
import { computed } from "vue";

// export const usePageProps = <T extends Record<string, any>>() => {
//     const page = usePage<PageProps & T>()
//     return page.props as T
// }

export const usePageProps = <T extends Record<string, any>>() => {
    return computed(() => {
        const page = usePage<PageProps & T>()
        return page.props as T
    })
}

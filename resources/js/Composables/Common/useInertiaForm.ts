import { useForm } from '@inertiajs/vue3'
import { keysToCamel } from '@/Utils/caseConverter'
import { FormDataType } from '@inertiajs/core'

/* eslint-disable */
export function useInertiaForm<T extends FormDataType<any>>(data: T) {
    const form = useForm(data)

    const originalPost = form.post.bind(form)
    const originalPut = form.put.bind(form)
    const originalPatch = form.patch.bind(form)
    const originalDelete = form.delete.bind(form)

    const wrapOptions = (options: Record<string, any> = {}) => {
        const originalOnError = options.onError
        return {
            ...options,
            onError: (errors: Record<string, string>) => {
                const camelErrors = keysToCamel(errors)
                form.clearErrors()
                form.setError(camelErrors)
                originalOnError?.(camelErrors)
            },
        }
    }

    form.post = (url, options?) => originalPost(url, wrapOptions(options))
    form.put = (url, options?) => originalPut(url, wrapOptions(options))
    form.patch = (url, options?) => originalPatch(url, wrapOptions(options))
    form.delete = (url, options?) => originalDelete(url, wrapOptions(options))

    return form
}

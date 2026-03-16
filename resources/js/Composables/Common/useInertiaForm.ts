import { useForm } from '@inertiajs/vue3'
import { keysToCamel, keysToSnake } from '@/Utils/caseConverter'
import { FormDataType } from '@inertiajs/core'

/* eslint-disable */
export function useInertiaForm<T extends FormDataType<any>>(data: T) {
    const form = useForm(data)

    const originalPost = form.post.bind(form)
    const originalPut = form.put.bind(form)
    const originalPatch = form.patch.bind(form)
    const originalDelete = form.delete.bind(form)

    const wrapOnError = (options: Record<string, any> = {}) => {
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

    const withSnakeTransform = (url: string, options: Record<string, any> | undefined, method: Function) => {
        // form.transform() でチェーンしてから送信
        form.transform((data) => {
            const userTransform = options?.transform
            const transformed = userTransform ? userTransform(data) : data
            return keysToSnake(transformed)
        })
        method(url, wrapOnError(options))
    }

    form.post = (url, options?) => withSnakeTransform(url, options, originalPost)
    form.put = (url, options?) => withSnakeTransform(url, options, originalPut)
    form.patch = (url, options?) => withSnakeTransform(url, options, originalPatch)
    form.delete = (url, options?) => withSnakeTransform(url, options, originalDelete)

    return form
}

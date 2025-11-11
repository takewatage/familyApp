import { Component, createApp, h, ref, App as VueApp } from 'vue'
import { createVuetify } from 'vuetify'
import { VDialog } from 'vuetify/components/VDialog'

export interface DialogOptions<T = any> {
    component: Component
    props?: Record<string, any>
    disableClose?: boolean
    maxWidth?: string | number
    persistent?: boolean
    fullscreen?: boolean
    transition?: string
}

export interface DialogInstance<T = any> {
    close: (result?: T) => void
    afterClosed: () => Promise<T | undefined>
}

// Global dialog state management
const dialogInstances = new Map<symbol, VueApp>()

export function useDialogService() {
    const open = <T = any>(options: DialogOptions<T>): DialogInstance<T> => {
        const dialogId = Symbol('dialog')
        let resolvePromise: (value: T | undefined) => void

        const afterClosedPromise = new Promise<T | undefined>((resolve) => {
            resolvePromise = resolve
        })

        const isOpen = ref(true)

        const close = (result?: T) => {
            isOpen.value = false

            // Clean up after transition
            setTimeout(() => {
                const app = dialogInstances.get(dialogId)
                if (app) {
                    app.unmount()
                    dialogInstances.delete(dialogId)
                }
                const container = document.getElementById(`dialog-${String(dialogId)}`)
                container?.remove()
                resolvePromise(result)
            }, 300)
        }

        // Create dialog wrapper component
        const DialogWrapper = {
            name: 'DialogWrapper',
            setup() {
                const handleModelValue = (val: boolean) => {
                    if (!val && !options.disableClose) {
                        close()
                    }
                }

                return () =>
                    h(
                        VDialog,
                        {
                            modelValue: isOpen.value,
                            'onUpdate:modelValue': handleModelValue,
                            maxWidth: options.maxWidth || '600px',
                            persistent: options.persistent ?? options.disableClose,
                            fullscreen: options.fullscreen,
                            transition: options.transition || 'dialog-transition',
                        },
                        {
                            default: () =>
                                h(options.component, {
                                    ...options.props,
                                    onClose: close,
                                }),
                        },
                    )
            },
        }

        // Create container and mount
        const container = document.createElement('div')
        container.id = `dialog-${String(dialogId)}`
        document.body.appendChild(container)

        const app = createApp(DialogWrapper)

        // Inherit Vuetify from current app context if available
        const currentVuetify = (window as any).__currentVuetifyInstance
        if (currentVuetify) {
            app.use(currentVuetify)
        } else {
            // Create new Vuetify instance if needed
            const vuetify = createVuetify()
            app.use(vuetify)
        }

        dialogInstances.set(dialogId, app)
        app.mount(container)

        return {
            close,
            afterClosed: () => afterClosedPromise,
        }
    }

    return {
        open,
    }
}

// Type-safe dialog component props interface
export interface DialogComponentProps<T = any> {
    onClose: (result?: T) => void
}

export function setupDialogPlugin(app: VueApp, vuetify: any) {
    ;(window as any).__currentVuetifyInstance = vuetify

    // Clean up on app unmount
    app.onUnmount(() => {
        delete (window as any).__currentVuetifyInstance
    })
}

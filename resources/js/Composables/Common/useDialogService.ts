/* eslint-disable */
import { Component, createApp, h, ref, App as VueApp, VNode } from 'vue'
import { createVuetify } from 'vuetify'
import { VDialog } from 'vuetify/components/VDialog'
import { VBtn } from 'vuetify/components/VBtn'
import { VCard, VCardText, VToolbar, VToolbarItems, VToolbarTitle } from 'vuetify/components'

export type ToolbarAction = {
    text: string
    variant?: 'text' | 'flat' | 'elevated' | 'tonal' | 'outlined' | 'plain'
    color?: string
    onClick?: <T>(close: (result?: T) => void) => void
}

export interface DialogOptions<T = any> {
    component: Component
    props?: Record<string, any>
    disableClose?: boolean
    maxWidth?: string | number
    persistent?: boolean
    fullscreen?: boolean
    transition?: string
    toolbar?: DialogToolbarOptions | boolean
}

export type DialogToolbarOptions = {
    title?: string
    showClose?: boolean
    closeIcon?: string
    color?: string
    density?: 'default' | 'comfortable' | 'compact'
    actions?: ToolbarAction[]
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

        const resolveToolbar = (): DialogToolbarOptions | null => {
            if (!options.toolbar) return null
            if (options.toolbar === true) {
                return { title: '', showClose: true }
            }
            return { showClose: true, ...options.toolbar }
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

                const renderToolbar = (): VNode | null => {
                    const toolbar = resolveToolbar()
                    if (!toolbar) return null

                    const children: VNode[] = []

                    // Close button
                    if (toolbar.showClose) {
                        children.push(
                            h(VBtn, {
                                icon: toolbar.closeIcon || 'mdi-close',
                                onClick: () => close(),
                            }),
                        )
                    }

                    // Title
                    if (toolbar.title) {
                        children.push(h(VToolbarTitle, null, { default: () => toolbar.title }))
                    }

                    // Actions
                    if (toolbar.actions?.length) {
                        children.push(
                            h(VToolbarItems, null, {
                                default: () =>
                                    toolbar.actions!.map((action, i) =>
                                        h(VBtn, {
                                            key: i,
                                            text: action.text,
                                            variant: action.variant || 'text',
                                            color: action.color,
                                            onClick: () => action.onClick?.(close),
                                        }),
                                    ),
                            }),
                        )
                    }

                    return h(
                        VToolbar,
                        {
                            color: toolbar.color,
                            density: toolbar.density || 'default',
                        },
                        { default: () => children },
                    )
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
                                h(VCard, null, {
                                    default: () => [
                                        renderToolbar(),
                                        h('div', null, [
                                            h(options.component, {
                                                ...options.props,
                                                onClose: close,
                                            }),
                                        ]),
                                    ],
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

declare global {
    interface Window {
        __currentVuetifyInstance?: ReturnType<typeof createVuetify>
    }
}

export function setupDialogPlugin(app: VueApp, vuetify: ReturnType<typeof createVuetify>) {
    window.__currentVuetifyInstance = vuetify

    // Clean up on app unmount
    app.onUnmount(() => {
        delete (window as any).__currentVuetifyInstance
    })
}

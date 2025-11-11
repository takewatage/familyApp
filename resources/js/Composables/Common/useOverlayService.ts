import { Component, createApp, h, ref, App as VueApp, Plugin, Teleport } from 'vue'
import { createVuetify } from 'vuetify'
import { VOverlay } from 'vuetify/components/VOverlay'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export interface BaseOverlayProps<TResult = unknown> {
    onClose: (result?: TResult) => void
}

export interface OverlayOptions<TProps = Record<string, unknown>, TResult = unknown> {
    component: Component<TProps & BaseOverlayProps<TResult>>
    props?: TProps
    persistent?: boolean
    scrim?: boolean | string
    width?: string | number
    height?: string | number
    maxWidth?: string | number
    maxHeight?: string | number
    fullscreen?: boolean
    transition?: string
    opacity?: number | string
    blur?: string | number
    contentClass?: string
    overlayClass?: string
    zIndex?: number | string
    location?: 'center' | 'top' | 'bottom' | 'start' | 'end' | 'top start' | 'top end' | 'bottom start' | 'bottom end'
    offset?: number | string | number[]
    noClickAnimation?: boolean
    closeOnBack?: boolean
}

export interface OverlayInstance<TResult = unknown> {
    close: (result?: TResult) => void
    afterClosed: () => Promise<TResult | undefined>
}

declare global {
    interface Window {
        __currentVuetifyInstance?: Plugin
    }
}

const overlayInstances = new Map<symbol, { app: VueApp; options: ref<OverlayOptions> }>()

export function useOverlayService() {
    const open = <TProps = Record<string, unknown>, TResult = unknown>(
        options: OverlayOptions<TProps, TResult>,
    ): OverlayInstance<TResult> => {
        const overlayId = Symbol('overlay')
        let resolvePromise: (value: TResult | undefined) => void

        const afterClosedPromise = new Promise<TResult | undefined>((resolve) => {
            resolvePromise = resolve
        })

        const isOpen = ref(true)
        const overlayOptions = ref(options)

        const close = (result?: TResult): void => {
            isOpen.value = false

            setTimeout(() => {
                const instance = overlayInstances.get(overlayId)
                if (instance) {
                    instance.app.unmount()
                    overlayInstances.delete(overlayId)
                }
                const container = document.getElementById(`overlay-${String(overlayId)}`)
                container?.remove()
                resolvePromise(result)
            }, 300)
        }

        // オーバーレイラッパーコンポーネント
        const OverlayWrapper = {
            name: 'OverlayWrapper',
            setup() {
                const handleClickOutside = () => {
                    if (!overlayOptions.value.persistent) {
                        close()
                    }
                }

                const handleKeydown = (e: KeyboardEvent) => {
                    if (e.key === 'Escape' && !overlayOptions.value.persistent) {
                        close()
                    }
                }

                // キーボードイベントリスナー
                if (typeof window !== 'undefined') {
                    window.addEventListener('keydown', handleKeydown)
                    const cleanup = () => window.removeEventListener('keydown', handleKeydown)
                    app.onUnmount(cleanup)
                }

                return () =>
                    h(
                        Teleport,
                        { to: 'body' },
                        h(
                            VOverlay,
                            {
                                modelValue: isOpen.value,
                                'onUpdate:modelValue': (val: boolean) => {
                                    if (!val) handleClickOutside()
                                },
                                persistent: overlayOptions.value.persistent ?? false,
                                scrim: overlayOptions.value.scrim ?? true,
                                opacity: overlayOptions.value.opacity,
                                blur: overlayOptions.value.blur,
                                class: overlayOptions.value.overlayClass,
                                contentClass: `overlay-content ${overlayOptions.value.contentClass || ''}`,
                                zIndex: overlayOptions.value.zIndex ?? 2000,
                                location: overlayOptions.value.location ?? 'center',
                                offset: overlayOptions.value.offset,
                                transition: overlayOptions.value.transition ?? 'fade-transition',
                                noClickAnimation: overlayOptions.value.noClickAnimation,
                                closeOnBack: overlayOptions.value.closeOnBack ?? true,
                                width: overlayOptions.value.width,
                                height: overlayOptions.value.height,
                                maxWidth: overlayOptions.value.maxWidth,
                                maxHeight: overlayOptions.value.maxHeight,
                                fullscreen: overlayOptions.value.fullscreen,
                                style: {
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                },
                            },
                            {
                                default: () =>
                                    h(
                                        options.component as Component,
                                        {
                                            ...options.props,
                                            onClose: close,
                                        } as TProps & BaseOverlayProps<TResult>,
                                    ),
                            },
                        ),
                    )
            },
        }

        // コンテナを作成してマウント
        const container = document.createElement('div')
        container.id = `overlay-${String(overlayId)}`
        document.body.appendChild(container)

        const app = createApp(OverlayWrapper)

        // Vuetifyインスタンスを取得または作成
        const currentVuetify = window.__currentVuetifyInstance

        if (currentVuetify) {
            app.use(currentVuetify)
        } else {
            const vuetify = createVuetify({
                components,
                directives,
            })
            app.use(vuetify)
        }

        overlayInstances.set(overlayId, {
            app,
            options: overlayOptions,
        })
        app.mount(container)

        return {
            close,
            afterClosed: () => afterClosedPromise,
        }
    }

    return { open }
}

// セットアップ関数
export function setupOverlayPlugin(app: VueApp, vuetify: Plugin): void {
    window.__currentVuetifyInstance = vuetify

    app.onUnmount(() => {
        delete window.__currentVuetifyInstance
    })
}

// 型付きオーバーレイの作成
export function createTypedOverlay<TProps = Record<string, unknown>, TResult = unknown>() {
    const overlay = useOverlayService()

    return {
        open: (options: OverlayOptions<TProps, TResult>) => overlay.open<TProps, TResult>(options),
    }
}

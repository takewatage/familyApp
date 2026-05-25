export type FooterApp = {
    key: string
    title: string
    shortTitle?: string
    icon: string
    route: string
}

export const FOOTER_APPS: FooterApp[] = [
    { key: 'home', title: 'ホーム', icon: 'mdi-home', route: '/home' },
    { key: 'dok', title: 'どっちがお得カネ', shortTitle: 'どっち?', icon: 'mdi-currency-usd', route: '/dok' },
    { key: 'tasks', title: 'TODOリスト', icon: 'mdi-format-list-checks', route: '/tasks' },
]

export const DEFAULT_FOOTER_ITEMS = ['home', 'dok', 'tasks']

export const REQUIRED_FOOTER_ITEMS = ['home']

/** フッターを非表示にするルートの前方一致リスト */
export const FOOTER_HIDDEN_ROUTES: string[] = ['/dok']

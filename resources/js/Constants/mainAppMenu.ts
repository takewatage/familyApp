export type MainAppMenuItem = {
    title: string
    icon: string
    route: string
}

export const mainAppMenuItems: MainAppMenuItem[] = [
    {
        title: 'ホーム',
        icon: 'mdi-home',
        route: '/home',
    },
    {
        title: 'どっちがお得カネ',
        icon: 'mdi-currency-usd',
        route: '/dok',
    },
    {
        title: 'TODOリスト',
        icon: 'mdi-format-list-checks',
        route: '/tasks',
    },
    {
        title: 'マイページ',
        icon: 'mdi-account-circle',
        route: '/mypage',
    },
]

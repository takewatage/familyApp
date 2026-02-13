export type MainAppMenuItem = {
    title: string
    icon: string
    route: string
}

export const mainAppMenuItems: MainAppMenuItem[] = [
    {
        title: 'どっちがお得カネ',
        icon: 'mdi-currency-usd',
        route: '/dok',
    },
    {
        title: 'TODOリスト',
        icon: 'mdi-format-list-checks',
        route: '/todo',
    },
]

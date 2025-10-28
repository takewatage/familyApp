export interface IUser {
    id: number
    name: string
    urlName: string
    email: string
    emailVerifiedAt: string | null
    role: number
    introduction: string
}

export interface IPost {
    id: number
    groupId: number
    userId: number
    title: string
    content: string
    viewAuthType: number | null
    createdAt: string
}

export interface ILink {
    title?: string
    href: string
    method?: string
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: IUser
    }
    appName: string
}

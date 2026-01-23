export type Task = {
    id: string
    content: string
    categoryId: string
    isCompleted: boolean
    createdAt: string
}

export type Category = {
    id: string
    name: string
    color: string
}

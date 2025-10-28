export type Task = {
    id: string
    description: string
    category_id: string
    is_completed: boolean
    created_at: string
}

export type Category = {
    id: string
    name: string
}

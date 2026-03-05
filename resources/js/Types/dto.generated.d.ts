export type MyPageData = {
    name: string
    email: string
    avatarUrl: string | null
    createdAt: string
}
export type ReorderTaskCategoryRequest = {
    categories: Array<SortCategoryRequestData>
}
export type SaveTaskRequestData = {
    id?: string | null
    familyId: string
    categoryId: string
    content: string
    color?: string | null
    memo?: string | null
}
export type SortCategoryRequestData = {
    id: string
    sort: number
}
export type StoreTaskCategoryRequest = {
    name: string
}
export type TaskCategoryData = {
    id: string
    name: string
    sort: number | null
}
export type TaskCategoryResult = {
    success: boolean
    category: TaskCategoryData
}
export type TaskData = {
    id: string
    familyId: string
    categoryId: string
    content: string
    color?: string | null
    memo?: string | null
    isCompleted: boolean
    completedAt?: string | null
    sort: number
}
export type TaskPageData = {
    categories: Array<TaskCategoryData>
    tasks: Array<TaskData>
    familyId: string
}
export type UpdateTaskCategoryRequest = {
    name: string
}

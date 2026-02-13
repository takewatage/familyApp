export type TaskCategoryData = {
    id: string
    name: string
    color: string
    sort: number | null
}
export type TaskData = {
    id: string
    content: string
    categoryId: string
    isCompleted: boolean
    sort: number | null
}
export type TaskPageData = {
    categories: Array<TaskCategoryData>
    tasks: Array<TaskData>
    familyId: string
    categoryId: string
}

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
    memo: string | null
    isCompleted: boolean
    sort: number | null
}
export type TaskPageData = {
    categories: Array<TaskCategoryData>
    tasks: Array<TaskData>
    familyId: string
}

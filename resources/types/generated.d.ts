export type DokResultData = {
categories: Array<TaskCategoryData>;
tasks: Array<TaskData>;
};
export type TaskCategoryData = {
id: string;
name: string;
color: string;
};
export type TaskData = {
id: string;
content: string;
categoryId: string;
isCompleted: boolean;
};

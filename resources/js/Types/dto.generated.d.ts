export type DeleteCompletedTasksRequest = {
categoryId: string;
};
export type FamilyData = {
id: string;
name: string;
code: string;
ownerId: string;
maxMembers: number;
settings?: Array<any>;
createdAt?: string;
updatedAt?: string;
deletedAt?: string;
owner?: UserData;
categories?: Array<TaskCategoryData>;
tasks?: Array<TaskData>;
};
export type FileData = {
id: string;
fileableType: string;
fileableId: string;
collection: string;
externalId: string;
directUrl: string;
name: string;
mimeType: string;
sort: string;
createdAt?: string;
updatedAt?: string;
fileable?: FileData;
};
export type MyPageData = {
user: UserData;
};
export type SaveTaskRequestData = {
id?: string;
familyId: string;
categoryId: string;
content: string;
color?: string;
memo?: string;
};
export type SortCategoryRequestData = {
id: string;
sort: number;
};
export type SortTaskCategoryRequest = {
categories: Array<SortCategoryRequestData>;
};
export type StoreTaskCategoryRequest = {
name: string;
};
export type TaskCategoryData = {
id: string;
familyId: string;
name: string;
sort: number;
createdAt?: string;
updatedAt?: string;
family?: FamilyData;
tasks?: Array<TaskData>;
};
export type TaskCategoryResult = {
success: boolean;
category: TaskCategoryData;
};
export type TaskData = {
id: string;
familyId: string;
categoryId: string;
content: string;
color?: string;
memo?: string;
isCompleted: boolean;
completedAt?: string;
sort: number;
createdAt?: string;
updatedAt?: string;
family?: FamilyData;
category?: TaskCategoryData;
};
export type TaskPageData = {
categories: Array<TaskCategoryData>;
tasks: Array<TaskData>;
familyId: string;
};
export type UpdateTaskCategoryRequest = {
name: string;
};
export type UserData = {
id: string;
name: string;
email: string;
emailVerifiedAt?: string;
createdAt?: string;
updatedAt?: string;
families?: Array<FamilyData>;
files?: Array<FileData>;
avatar?: FileData;
};

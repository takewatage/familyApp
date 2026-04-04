export type DeleteCompletedTasksRequest = {
categoryId: string;
};
export type FamilyData = {
id: string;
name: string;
code: string;
codeExpiresAt?: string;
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
export type FamilyForSwitchData = {
id: string;
name: string;
memberNames: Array<string>;
};
export type FamilyMemberData = {
id: string;
name: string;
role: string;
avatar?: FileData | null;
};
export type FamilyMembersResult = {
family: FamilyData;
members: Array<FamilyMemberData>;
virtualUsers: Array<VirtualUserData>;
isOwner: boolean;
};
export type FamilySettingsResult = {
family: FamilyData;
isOwner: boolean;
};
export type FamilySwitchResult = {
families: Array<FamilyForSwitchData>;
currentFamilyId?: string;
};
export type FileData = {
id: string;
fileableType: string;
fileableId: string;
collection: string;
path: string;
url: string;
name: string;
mimeType: string;
sort: string;
createdAt?: string;
updatedAt?: string;
};
export type MyPageData = {
user: UserData;
};
export type SaveProfileData = {
name: string;
birthday?: string;
avatarImage?: any;
};
export type SaveTaskRequestData = {
id?: string;
familyId: string;
categoryId: string;
content: string;
color?: string;
memo?: string;
};
export type SaveVirtualUserRequest = {
name: string;
avatarImage?: any;
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
birthday?: string;
emailVerifiedAt?: string;
createdAt?: string;
updatedAt?: string;
avatar?: FileData | null;
families?: Array<FamilyData>;
files?: Array<FileData>;
};
export type UserSettingsResult = {
theme: string;
themeColor: string;
};
export type VirtualUserData = {
id: string;
familyId: string;
name: string;
createdAt?: string;
updatedAt?: string;
avatar?: FileData | null;
};

export type CategoriesPageResult = {
categories: Array<CategoryData>;
};
export type CategoryData = {
id: string;
familyId?: string;
parentId?: string;
name: string;
icon?: string;
color: string;
sortOrder: number;
isSystem: boolean;
isActive: boolean;
};
export type DeleteCompletedTasksRequest = {
categoryId: string;
};
export type ExpenseData = {
id: string;
familyId: string;
memberType?: string;
memberId?: string;
memberName?: string;
categoryId: string;
categoryName: string;
categoryColor: string;
categoryIcon?: string;
paymentMethodId: string;
paymentMethodName: string;
shopId?: string;
shopName?: string;
shopDisplayName?: string;
amount: string;
expenseDate: string;
memo?: string;
isRecurring: boolean;
createdAt?: string;
};
export type ExpensePageResult = {
expenses: Array<ExpenseData>;
categories: Array<CategoryData>;
paymentMethods: Array<PaymentMethodData>;
shops: Array<ShopData>;
quickEntries: Array<QuickEntryData>;
memberOptions: Array<MemberOptionData>;
yearMonth: string;
totalAmount: string;
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
inviteUrls: { [key: string]: string };
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
export type FooterSettingsResult = {
footerItems: Array<string>;
};
export type HomeResult = {
members: Array<FamilyMemberData>;
virtualUsers: Array<VirtualUserData>;
};
export type InviteConfirmResult = {
familyName?: string;
memberCount: number;
code: string;
alreadyJoined: boolean;
isFull: boolean;
error?: string;
inviteRole: string;
};
export type MemberOptionData = {
key: string;
memberType: string;
memberId: string;
name: string;
};
export type MyPageData = {
user: UserData;
};
export type PaymentMethodData = {
id: string;
familyId?: string;
name: string;
icon?: string;
sortOrder: number;
isSystem: boolean;
isActive: boolean;
};
export type PaymentMethodsPageResult = {
paymentMethods: Array<PaymentMethodData>;
};
export type QuickEntriesPageResult = {
quickEntries: Array<QuickEntryData>;
categories: Array<CategoryData>;
paymentMethods: Array<PaymentMethodData>;
shops: Array<ShopData>;
};
export type QuickEntryData = {
id: string;
familyId: string;
name: string;
categoryId: string;
categoryName: string;
categoryColor: string;
categoryIcon?: string;
paymentMethodId: string;
paymentMethodName: string;
shopId?: string;
shopName?: string;
defaultAmount?: string;
sortOrder: number;
usageCount: number;
};
export type RegisterPageResult = {
familyName: string;
};
export type ReorderCategoriesRequest = {
categories: Array<SortItemData>;
};
export type SaveProfileData = {
name: string;
birthday?: string;
avatarImage?: any;
deleteAvatar: boolean;
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
export type ShopData = {
id: string;
familyId: string;
name: string;
defaultCategoryId?: string;
usageCount: number;
};
export type ShopsPageResult = {
shops: Array<ShopData>;
categories: Array<CategoryData>;
};
export type SortItemData = {
id: string;
sort: number;
};
export type SortTaskCategoryRequest = {
categories: Array<SortItemData>;
};
export type StoreCategoryRequest = {
name: string;
color: string;
icon?: string;
parentId?: string;
};
export type StoreExpenseRequest = {
amount: string;
categoryId: string;
paymentMethodId: string;
expenseDate: string;
shopId?: string;
shopName?: string;
memberType?: string;
memberId?: string;
memo?: string;
};
export type StorePaymentMethodRequest = {
name: string;
icon?: string;
};
export type StoreQuickEntryRequest = {
name: string;
categoryId: string;
paymentMethodId: string;
shopId?: string;
defaultAmount?: string;
};
export type StoreShopRequest = {
name: string;
defaultCategoryId?: string;
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
export type UpdateCategoryRequest = {
name: string;
color: string;
icon?: string;
parentId?: string;
};
export type UpdateExpenseRequest = {
amount: string;
categoryId: string;
paymentMethodId: string;
expenseDate: string;
shopId?: string;
shopName?: string;
memberType?: string;
memberId?: string;
memo?: string;
};
export type UpdateShopRequest = {
name: string;
defaultCategoryId?: string;
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
themeName: string;
footerItems: Array<string>;
};
export type VirtualUserData = {
id: string;
familyId: string;
name: string;
createdAt?: string;
updatedAt?: string;
avatar?: FileData | null;
};

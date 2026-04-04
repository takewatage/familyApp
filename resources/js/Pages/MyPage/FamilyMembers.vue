<script setup lang="ts">
import { ref } from 'vue'
import DokLayout from '@/Layouts/DokLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { router } from '@inertiajs/vue3'
import type { FamilyMembersResult, VirtualUserData } from '@/Types/dto.generated'
import VirtualUserDialog from '@/Components/Family/VirtualUserDialog.vue'

defineOptions({ layout: DokLayout })

const props = usePageProps<FamilyMembersResult>()
const { open } = useDialogService()
const snackbar = useSnackbar()

const removeTargetId = ref<string | null>(null)
const removeConfirmDialog = ref(false)

const deleteVirtualUserTargetId = ref<string | null>(null)
const deleteVirtualUserDialog = ref(false)

function confirmRemoveMember(userId: string) {
    removeTargetId.value = userId
    removeConfirmDialog.value = true
}

function removeMember() {
    if (!removeTargetId.value) {
        return
    }

    router.delete(route('family.members.destroy', { user: removeTargetId.value }), {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success('メンバーを除名しました')
            removeConfirmDialog.value = false
        },
        onError: () => snackbar.error('除名に失敗しました'),
    })
}

function openAddVirtualUser() {
    open({
        component: VirtualUserDialog,
        toolbar: { title: '仮想メンバーを追加' },
        maxWidth: 480,
    })
}

function openEditVirtualUser(virtualUser: VirtualUserData) {
    open({
        component: VirtualUserDialog,
        props: { virtualUser },
        toolbar: { title: '仮想メンバーを編集' },
        maxWidth: 480,
    })
}

function confirmDeleteVirtualUser(id: string) {
    deleteVirtualUserTargetId.value = id
    deleteVirtualUserDialog.value = true
}

function deleteVirtualUser() {
    if (!deleteVirtualUserTargetId.value) {
        return
    }

    router.delete(route('family.virtual-users.destroy', { virtualUser: deleteVirtualUserTargetId.value }), {
        preserveScroll: true,
        onSuccess: () => {
            snackbar.success('仮想メンバーを削除しました')
            deleteVirtualUserDialog.value = false
        },
        onError: () => snackbar.error('削除に失敗しました'),
    })
}

function roleLabel(role: string): string {
    return role === 'owner' ? 'オーナー' : 'メンバー'
}
</script>

<template>
    <v-container>
        <v-row justify="center">
            <v-col
                cols="12"
                sm="8"
                md="6">
                <div class="d-flex align-center mb-4">
                    <v-btn
                        icon="mdi-arrow-left"
                        variant="text"
                        @click="router.visit(route('mypage.index'))"/>
                    <span class="text-h6 ml-2">メンバー管理</span>
                </div>

                <!-- 実メンバー一覧 -->
                <v-card>
                    <v-card-title>
                        メンバー ({{ props.members.length }}名)
                    </v-card-title>
                    <v-list>
                        <template
                            v-for="(member, index) in props.members"
                            :key="member.id">
                            <v-list-item>
                                <template #prepend>
                                    <v-avatar
                                        color="primary"
                                        size="40">
                                        <v-img
                                            v-if="member.avatar"
                                            :src="member.avatar.url"/>
                                        <v-icon
                                            v-else
                                            icon="mdi-account"
                                            color="white"/>
                                    </v-avatar>
                                </template>
                                <v-list-item-title>{{ member.name }}</v-list-item-title>
                                <v-list-item-subtitle>{{ roleLabel(member.role) }}</v-list-item-subtitle>
                                <template #append>
                                    <v-btn
                                        v-if="props.isOwner && member.role !== 'owner'"
                                        size="small"
                                        variant="text"
                                        color="error"
                                        @click="confirmRemoveMember(member.id)">
                                        除名
                                    </v-btn>
                                </template>
                            </v-list-item>
                            <v-divider v-if="index < props.members.length - 1"/>
                        </template>
                    </v-list>
                </v-card>

                <!-- 招待コード -->
                <v-card class="mt-4">
                    <v-card-title>招待コード</v-card-title>
                    <v-card-text>
                        <div class="d-flex align-center gap-2">
                            <span class="text-h6 font-weight-bold letter-spacing-wide">
                                {{ props.family.code }}
                            </span>
                            <v-btn
                                icon="mdi-content-copy"
                                size="small"
                                variant="text"
                                @click="navigator.clipboard.writeText(props.family.code)"/>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- 仮想メンバー一覧 -->
                <v-card class="mt-4">
                    <v-card-title>仮想メンバー ({{ props.virtualUsers.length }}名)</v-card-title>
                    <v-list v-if="props.virtualUsers.length > 0">
                        <template
                            v-for="(vu, index) in props.virtualUsers"
                            :key="vu.id">
                            <v-list-item>
                                <template #prepend>
                                    <v-avatar
                                        color="secondary"
                                        size="40">
                                        <v-img
                                            v-if="vu.avatar"
                                            :src="vu.avatar.url"/>
                                        <v-icon
                                            v-else
                                            icon="mdi-account-outline"
                                            color="white"/>
                                    </v-avatar>
                                </template>
                                <v-list-item-title>{{ vu.name }}</v-list-item-title>
                                <v-list-item-subtitle>仮想メンバー</v-list-item-subtitle>
                                <template #append>
                                    <v-btn
                                        icon="mdi-pencil"
                                        size="small"
                                        variant="text"
                                        @click="openEditVirtualUser(vu)"/>
                                    <v-btn
                                        icon="mdi-delete"
                                        size="small"
                                        variant="text"
                                        color="error"
                                        @click="confirmDeleteVirtualUser(vu.id)"/>
                                </template>
                            </v-list-item>
                            <v-divider v-if="index < props.virtualUsers.length - 1"/>
                        </template>
                    </v-list>
                    <v-card-text v-else>
                        <p class="text-body-2 text-medium-emphasis">仮想メンバーはいません</p>
                    </v-card-text>
                    <v-card-actions>
                        <v-btn
                            prepend-icon="mdi-plus"
                            variant="tonal"
                            color="primary"
                            @click="openAddVirtualUser">
                            仮想メンバーを追加
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>

    <!-- 仮想メンバー削除確認ダイアログ -->
    <v-dialog
        v-model="deleteVirtualUserDialog"
        max-width="360">
        <v-card>
            <v-card-title>仮想メンバーを削除しますか？</v-card-title>
            <v-card-text>
                この操作は取り消せません。
            </v-card-text>
            <v-card-actions>
                <v-btn
                    variant="text"
                    @click="deleteVirtualUserDialog = false">
                    キャンセル
                </v-btn>
                <v-spacer/>
                <v-btn
                    color="error"
                    variant="flat"
                    @click="deleteVirtualUser">
                    削除する
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- 除名確認ダイアログ -->
    <v-dialog
        v-model="removeConfirmDialog"
        max-width="360">
        <v-card>
            <v-card-title>メンバーを除名しますか？</v-card-title>
            <v-card-text>
                この操作は取り消せません。
            </v-card-text>
            <v-card-actions>
                <v-btn
                    variant="text"
                    @click="removeConfirmDialog = false">
                    キャンセル
                </v-btn>
                <v-spacer/>
                <v-btn
                    color="error"
                    variant="flat"
                    @click="removeMember">
                    除名する
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

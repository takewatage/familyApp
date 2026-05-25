<script setup lang="ts">
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useDialogService } from '@/Composables/Common/useDialogService'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { useConfirmDialog } from '@/Composables/Common/useConfirmDialogService'
import { router } from '@inertiajs/vue3'
import type { FamilyMembersResult, VirtualUserData } from '@/Types/dto.generated'
import VirtualUserDialog from '@/Components/Family/VirtualUserDialog.vue'
import InviteBottomSheet from '@/Components/Family/InviteBottomSheet.vue'

defineOptions({ layout: AuthenticatedLayout })

const props = usePageProps<FamilyMembersResult>()
const { open } = useDialogService()
const snackbar = useSnackbar()
const { confirm } = useConfirmDialog()

const inviteSheetVisible = ref(false)

async function removeMember(userId: string) {
    const ok = await confirm({
        title: 'メンバーを除名しますか？',
        message: 'この操作は取り消せません。',
        confirmText: '除名する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('family.members.destroy', { user: userId }), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('メンバーを除名しました'),
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

async function deleteVirtualUser(id: string) {
    const ok = await confirm({
        title: '仮想メンバーを削除しますか？',
        message: 'この操作は取り消せません。',
        confirmText: '削除する',
        confirmColor: 'error',
    })

    if (!ok) {
        return
    }

    router.delete(route('family.virtual-users.destroy', { virtualUser: id }), {
        preserveScroll: true,
        onSuccess: () => snackbar.success('仮想メンバーを削除しました'),
        onError: () => snackbar.error('削除に失敗しました'),
    })
}

function roleLabel(role: string): string {
    const labels: Record<string, string> = {
        owner: 'オーナー',
        parent: '保護者',
        child: '子ども',
        guest: 'メンバー',
    }
    return labels[role] ?? 'メンバー'
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
                                        @click="removeMember(member.id)">
                                        除名
                                    </v-btn>
                                </template>
                            </v-list-item>
                            <v-divider/>
                        </template>
                    </v-list>

                    <v-card-actions class="pa-2">
                        <v-btn
                            block
                            color="primary"
                            variant="tonal"
                            prepend-icon="mdi-account-plus"
                            @click="inviteSheetVisible = true">
                            メンバーを招待する
                        </v-btn>
                    </v-card-actions>

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
                                        @click="deleteVirtualUser(vu.id)"/>
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

    <!-- 招待ボトムシート -->
    <InviteBottomSheet
        v-model="inviteSheetVisible"
        :family-name="props.family.name"
        :invite-urls="props.inviteUrls"/>
</template>

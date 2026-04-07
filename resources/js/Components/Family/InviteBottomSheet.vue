<script setup lang="ts">
import { ref, computed } from 'vue'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import InviteQrDialog from '@/Components/Family/InviteQrDialog.vue'

const props = defineProps<{
    modelValue: boolean
    familyName: string
    inviteUrls: Record<string, string>
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
}>()

const snackbar = useSnackbar()

const selectedRole = ref('guest')
const qrDialogVisible = ref(false)
const qrUrl = ref('')

const roleItems = [
    { title: 'メンバー', value: 'guest' },
    { title: '保護者', value: 'parent' },
    { title: '子ども', value: 'child' },
]

const inviteUrl = computed(() => props.inviteUrls[selectedRole.value] ?? '')

const inviteMessage = computed(() =>
    `${props.familyName}への招待が届いています！\n下記のリンクをタップしてアカウントを作成してください。\n${inviteUrl.value}`,
)

async function copyInviteMessage() {
    await navigator.clipboard.writeText(inviteMessage.value)
    snackbar.success('招待メッセージをコピーしました')
}

function showQrCode() {
    qrUrl.value = inviteUrl.value
    qrDialogVisible.value = true
}
</script>

<template>
    <v-bottom-sheet
        :model-value="props.modelValue"
        @update:model-value="emit('update:modelValue', $event)">
        <v-card rounded="t-xl">
            <v-card-text class="pt-6 pb-8">
                <p class="text-h6 font-weight-bold mb-1">グループメンバーを招待</p>
                <p class="text-body-2 text-medium-emphasis mb-6">
                    家族やパートナーを招待しましょう。
                </p>

                <v-select
                    v-model="selectedRole"
                    label="招待する役割"
                    :items="roleItems"
                    variant="outlined"
                    density="comfortable"
                    class="mb-5"/>

                <v-btn
                    block
                    variant="tonal"
                    color="primary"
                    size="large"
                    prepend-icon="mdi-share-variant"
                    class="mb-3"
                    @click="copyInviteMessage">
                    招待リンクを送る
                </v-btn>
                <v-btn
                    block
                    variant="tonal"
                    size="large"
                    prepend-icon="mdi-qrcode"
                    @click="showQrCode">
                    QRコードを表示する
                </v-btn>
            </v-card-text>
        </v-card>
    </v-bottom-sheet>

    <InviteQrDialog
        v-model="qrDialogVisible"
        :url="qrUrl"
        :family-name="props.familyName"/>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import QRCode from 'qrcode'

const props = defineProps<{
    modelValue: boolean
    url: string
    familyName: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
}>()

const qrDataUrl = ref('')

watch(
    () => props.modelValue,
    async (visible) => {
        if (visible && props.url) {
            qrDataUrl.value = await QRCode.toDataURL(props.url, { width: 240, margin: 2 })
        }
    },
)
</script>

<template>
    <v-dialog
        :model-value="props.modelValue"
        max-width="320"
        @update:model-value="emit('update:modelValue', $event)">
        <v-card rounded="xl">
            <v-card-title class="pt-5 px-5 text-h6 font-weight-bold">
                QRコードで招待
            </v-card-title>
            <v-card-subtitle class="px-5 pb-2">
                {{ props.familyName }}
            </v-card-subtitle>
            <v-card-text class="d-flex justify-center pb-4">
                <v-img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    width="240"
                    height="240"/>
                <v-progress-circular
                    v-else
                    indeterminate
                    color="primary"/>
            </v-card-text>
            <v-card-actions class="pb-4 px-4">
                <v-spacer/>
                <v-btn
                    variant="tonal"
                    @click="emit('update:modelValue', false)">
                    閉じる
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

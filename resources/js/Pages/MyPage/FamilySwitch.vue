<script setup lang="ts">
import DokLayout from '@/Layouts/DokLayout.vue'
import { usePageProps } from '@/Composables/Common/usePageProps'
import { useSnackbar } from '@/Composables/Common/useSnackbar'
import { router } from '@inertiajs/vue3'
import type { FamilySwitchResult } from '@/Types/dto.generated'

defineOptions({ layout: DokLayout })

const props = usePageProps<FamilySwitchResult>()
const snackbar = useSnackbar()

function switchFamily(familyId: string) {
    if (familyId === props.value.currentFamilyId) {
        return
    }

    router.post(
        route('family.switch', { family: familyId }),
        {},
        {
            onError: () => snackbar.error('切り替えに失敗しました'),
        },
    )
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
                    <span class="text-h6 ml-2">家族切り替え</span>
                </div>

                <v-card>
                    <v-list>
                        <template
                            v-for="(family, index) in props.families"
                            :key="family.id">
                            <v-list-item
                                :class="{ 'bg-primary-lighten-4': family.id === props.currentFamilyId }"
                                @click="switchFamily(family.id)">
                                <template #prepend>
                                    <v-icon
                                        v-if="family.id === props.currentFamilyId"
                                        icon="mdi-check-circle"
                                        color="primary"/>
                                    <v-icon
                                        v-else
                                        icon="mdi-circle-outline"
                                        color="grey"/>
                                </template>
                                <v-list-item-title class="font-weight-medium">
                                    {{ family.name }}
                                    <v-chip
                                        v-if="family.id === props.currentFamilyId"
                                        size="x-small"
                                        color="primary"
                                        class="ml-2">
                                        現在
                                    </v-chip>
                                </v-list-item-title>
                                <v-list-item-subtitle>
                                    {{ family.memberNames.join('・') }}
                                </v-list-item-subtitle>
                            </v-list-item>
                            <v-divider v-if="index < props.families.length - 1"/>
                        </template>
                    </v-list>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

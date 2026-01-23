<!-- DiscountBottomSheet.vue -->
<script setup lang="ts">
import { ref, computed, defineProps, defineEmits, watch } from 'vue'

interface Props {
    modelValue: boolean
    currentPrice: string | number
}

const props = defineProps<Props>()
const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    apply: [discountedPrice: string]
    close: []
}>()

// 選択された割引率
const selectedDiscount = ref<number>(0)

// 元の価格を数値に変換
const originalPrice = computed(() => {
    if (typeof props.currentPrice === 'string') {
        return parseFloat(props.currentPrice.replace(/,/g, '').replace(/[^\d.-]/g, '')) || 0
    }
    return props.currentPrice || 0
})

// 割引額
const discountAmount = computed(() => {
    return originalPrice.value * (selectedDiscount.value / 100)
})

// 割引後の価格
const discountedPrice = computed(() => {
    const price = originalPrice.value - discountAmount.value
    return Math.ceil(price * 100) / 100
})

// 表示用フォーマット
const formatPrice = (price: number): string => {
    return price.toLocaleString('ja-JP', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })
}

// 割引率のリスト（5%刻みで5%～95%）
const discountRates = computed(() => {
    const rates = []
    for (let i = 5; i <= 95; i += 5) {
        rates.push(i)
    }
    return rates
})

// bottom sheetの表示状態
const sheet = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
})

// 割引率を選択
const selectDiscount = (rate: number) => {
    selectedDiscount.value = rate
}

// 適用
const applyDiscount = () => {
    if (selectedDiscount.value > 0) {
        emit('apply', discountedPrice.value.toString())
        sheet.value = false
        // リセット
        setTimeout(() => {
            selectedDiscount.value = 0
        }, 300)
    }
}

// キャンセル
const cancel = () => {
    sheet.value = false
    selectedDiscount.value = 0
    emit('close')
}

// ダイアログが開いた時にリセット
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal) {
            console.log(props)
            selectedDiscount.value = 0
        }
    },
)
</script>

<template>
    <v-bottom-sheet
        v-model="sheet"
        inset>
        <v-card class="discount-bottom-sheet">
            <!-- ヘッダー -->
            <v-card-title class="d-flex align-center pa-2">
                <v-icon
                    class="mr-2"
                    color="primary">
                    mdi-percent
                </v-icon>
                <span class="h-4">割引率を選択</span>
                <v-spacer />
                <v-btn
                    icon
                    variant="text"
                    @click="cancel">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>

            <v-divider />

            <!-- 現在の計算プレビュー -->
            <v-card-text class="pa-4">
                <v-sheet
                    class="calculation-preview pa-4"
                    color="grey-lighten-5"
                    rounded="lg">
                    <div class="d-flex align-center justify-space-between mb-2">
                        <span class="text-body-2">元の価格</span>
                        <span class="text-body-1 font-weight-medium">¥{{ formatPrice(originalPrice) }}</span>
                    </div>

                    <v-fade-transition>
                        <div v-if="selectedDiscount > 0">
                            <div class="d-flex align-center justify-space-between mb-2">
                                <span class="text-body-2 text-red">割引 ({{ selectedDiscount }}%)</span>
                                <span class="text-body-1 text-red">-¥{{ formatPrice(discountAmount) }}</span>
                            </div>

                            <v-divider class="my-2" />

                            <div class="d-flex align-center justify-space-between">
                                <span class="text-body-1 font-weight-bold">割引後価格</span>
                                <span class="text-h6 font-weight-bold text-primary">¥{{ formatPrice(discountedPrice) }}</span>
                            </div>
                        </div>
                    </v-fade-transition>
                </v-sheet>

                <v-divider class="my-3" />

                <!-- 全割引率リスト -->
                <div>
                    <div class="discount-grid">
                        <v-btn
                            v-for="rate in discountRates"
                            :key="rate"
                            :variant="selectedDiscount === rate ? 'flat' : 'outlined'"
                            :color="selectedDiscount === rate ? 'primary' : 'grey'"
                            :class="{ selected: selectedDiscount === rate }"
                            size="small"
                            @click="selectDiscount(rate)">
                            {{ rate }}%
                        </v-btn>
                    </div>
                </div>
            </v-card-text>

            <!-- アクションボタン -->
            <v-divider />
            <v-card-actions class="pa-4">
                <v-btn
                    variant="outlined"
                    block
                    class="mr-2"
                    @click="cancel">
                    キャンセル
                </v-btn>
                <v-btn
                    variant="flat"
                    color="primary"
                    block
                    :disabled="selectedDiscount === 0"
                    @click="applyDiscount">
                    適用する
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-bottom-sheet>
</template>

<style lang="scss" scoped>
.discount-bottom-sheet {
    max-height: 85vh;
    overflow-y: auto;
}

.calculation-preview {
    transition: all 0.3s ease;

    .v-divider {
        opacity: 0.5;
    }
}

.discount-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;

    @media (min-width: 600px) {
        grid-template-columns: repeat(5, 1fr);
    }

    .v-btn {
        transition: all 0.2s ease;

        &.selected {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        &:hover:not(.selected) {
            transform: scale(1.02);
        }
    }
}

// スクロール時のヘッダー固定
.v-card-title {
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
}

.v-card-actions {
    position: sticky;
    bottom: 0;
    background: white;
    z-index: 1;
    display: flex;
    gap: 8px;

    .v-btn {
        flex: 1;
    }
}

// アニメーション
.v-fade-transition-enter-active,
.v-fade-transition-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s ease;
}

.v-fade-transition-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.v-fade-transition-leave-to {
    opacity: 0;
    transform: translateY(10px);
}
</style>

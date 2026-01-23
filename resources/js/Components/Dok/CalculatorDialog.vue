<script setup lang="ts">
import MultiButtons from '@/Components/Dok/MultiButtons.vue'

import { computed, ref, watch } from 'vue'
import { ButtonOption } from '@/Types/dok'

// export interface CalculatorEmits {
//     'update:modelValue': [value: boolean]
//     complete: [result: string]
// }
interface Props {
    modelValue: boolean
    initValue?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    complete: [result: string]
    close: [result: null]
}>()

// 電卓の状態管理
const currentInput = ref('0')
const previousInput = ref('')
const currentOperation = ref<string | null>(null)
const shouldResetNext = ref(false)
const isNewCalculation = ref(true)

// 表示用の計算式
const displayExpression = computed(() => {
    if (!previousInput.value && !currentOperation.value) {
        return ''
    }

    let expr = previousInput.value
    if (currentOperation.value) {
        expr += ` ${currentOperation.value}`
        if (!shouldResetNext.value && currentInput.value !== '0') {
            expr += ` ${currentInput.value}`
        }
    }
    return expr
})

// 表示用の現在値（カンマ区切り）
const displayValue = computed(() => {
    const num = parseFloat(currentInput.value)
    if (isNaN(num)) return currentInput.value

    // 小数点がある場合の処理
    if (currentInput.value.includes('.')) {
        const parts = currentInput.value.split('.')
        const integerPart = parseInt(parts[0]).toLocaleString('ja-JP')
        return `${integerPart}.${parts[1]}`
    }

    return num.toLocaleString('ja-JP')
})

// ボタン定義
const buttons: ButtonOption[] = [
    // 1行目 - 演算子
    { icon: 'mdi-plus', color: 'primary', action: () => handleOperation('+'), variant: 'flat', isDark: true },
    { icon: 'mdi-minus', color: 'primary', action: () => handleOperation('-'), variant: 'flat', isDark: true },
    { icon: 'mdi-close', color: 'primary', action: () => handleOperation('*'), variant: 'flat', isDark: true },
    { icon: 'mdi-division', color: 'primary', action: () => handleOperation('/'), variant: 'flat', isDark: true },

    // 2行目
    { label: '7', action: () => inputNumber('7') },
    { label: '8', action: () => inputNumber('8') },
    { label: '9', action: () => inputNumber('9') },
    { label: 'C', color: 'error', action: clear },

    // 3行目
    { label: '4', action: () => inputNumber('4') },
    { label: '5', action: () => inputNumber('5') },
    { label: '6', action: () => inputNumber('6') },
    { icon: 'mdi-backspace-outline', color: 'black', action: backspace },

    // 4行目
    { label: '1', action: () => inputNumber('1') },
    { label: '2', action: () => inputNumber('2') },
    { label: '3', action: () => inputNumber('3') },
    { label: '=', color: 'primary', action: calculate },

    // 5行目
    { label: '0', action: () => inputNumber('0') },
    { label: '.', action: () => inputDecimal() },
    { label: '完了', color: 'success', colSpan: 2, action: complete },
]

// 数字入力
function inputNumber(num: string) {
    if (shouldResetNext.value) {
        currentInput.value = num
        shouldResetNext.value = false
        isNewCalculation.value = false
    } else if (currentInput.value === '0' && num !== '0') {
        currentInput.value = num
    } else if (currentInput.value !== '0' || num !== '0') {
        // 最大桁数制限（整数部15桁）
        const parts = currentInput.value.split('.')
        if (parts[0].replace(/,/g, '').length < 15) {
            currentInput.value += num
        }
    }
}

// 小数点入力
function inputDecimal() {
    if (shouldResetNext.value) {
        currentInput.value = '0.'
        shouldResetNext.value = false
        isNewCalculation.value = false
    } else if (!currentInput.value.includes('.')) {
        currentInput.value += '.'
    }
}

// 演算子処理
function handleOperation(op: string) {
    if (currentOperation.value && !shouldResetNext.value) {
        calculate()
    }

    previousInput.value = currentInput.value
    currentOperation.value = op
    shouldResetNext.value = true
    isNewCalculation.value = false
}

// 計算実行
function calculate() {
    if (!currentOperation.value || !previousInput.value) return

    const prev = parseFloat(previousInput.value)
    const current = parseFloat(currentInput.value)
    let result = 0

    switch (currentOperation.value) {
        case '+':
            result = prev + current
            break
        case '-':
            result = prev - current
            break
        case '*':
            result = prev * current
            break
        case '/':
            if (current === 0) {
                currentInput.value = 'エラー'
                clear()
                return
            }
            result = prev / current
            break
    }

    // 結果を適切にフォーマット
    currentInput.value = formatResult(result)
    previousInput.value = ''
    currentOperation.value = null
    shouldResetNext.value = true
    isNewCalculation.value = true
}

// 結果のフォーマット
function formatResult(num: number): string {
    // 非常に大きな数や小さな数の場合は指数表記
    if (Math.abs(num) >= 1e15 || (Math.abs(num) < 1e-10 && num !== 0)) {
        return num.toExponential(10)
    }

    // 小数点以下10桁まで表示（末尾の0は削除）
    return parseFloat(num.toFixed(10)).toString()
}

// バックスペース
function backspace() {
    if (currentInput.value.length > 1) {
        currentInput.value = currentInput.value.slice(0, -1)
    } else {
        currentInput.value = '0'
    }
}

// クリア
function clear() {
    currentInput.value = '0'
    previousInput.value = ''
    currentOperation.value = null
    shouldResetNext.value = false
    isNewCalculation.value = true
}

// 完了
function complete() {
    emit('complete', currentInput.value)
    emit('update:modelValue', false)
    // ダイアログを閉じた後にリセット
    setTimeout(() => clear(), 300)
}

// ボタンクリックハンドラー
function handleButtonClick(button: ButtonOption) {
    if (button.action) {
        button.action()
    }
}

// ダイアログの表示状態
const dialog = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
})

// ダイアログが開いた時に初期値を設定
watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal && props.initValue) {
            // 初期値から不要な文字（カンマなど）を除去して設定
            const cleanValue = props.initValue.replace(/,/g, '').replace(/[^\d.-]/g, '')
            if (cleanValue && !isNaN(parseFloat(cleanValue))) {
                currentInput.value = cleanValue
                shouldResetNext.value = false
                isNewCalculation.value = false
            }
        } else {
            currentInput.value = '0'
        }
    },
)

const close = () => {
    emit('close', null)
    dialog.value = false
}
</script>

<template>
    <v-dialog
        v-model="dialog"
        transition="dialog-bottom-transition"
        fullscreen>
        <v-card class="calculator-dialog">
            <!-- ヘッダー -->
            <v-toolbar
                density="compact"
                color="primary"
                dark
                flat>
                <v-toolbar-title>電卓</v-toolbar-title>
                <v-spacer />
                <v-btn
                    icon
                    @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-toolbar>

            <!-- 電卓本体 -->
            <v-card-text class="calculator-body pa-0">
                <div class="calculator-container">
                    <!-- ディスプレイエリア -->
                    <div class="display-area">
                        <!-- 計算式表示 -->
                        <div class="expression-display">
                            {{ displayExpression }}
                        </div>
                        <!-- 現在値表示 -->
                        <div class="value-display">
                            {{ displayValue }}
                        </div>
                    </div>

                    <!-- ボタンエリア -->
                    <div class="buttons-area">
                        <MultiButtons
                            :buttons="buttons"
                            :columns="4"
                            :rows="5"
                            @button-click="handleButtonClick" />
                    </div>
                </div>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<style lang="scss" scoped>
.calculator-dialog {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: #ffffff;
}

.calculator-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.calculator-container {
    display: flex;
    flex-direction: column;
    padding: 16px;
    gap: 16px;
    height: 80vh;
}

.display-area {
    background-color: #f5f5f5;
    border-radius: 12px;
    padding: 20px;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.expression-display {
    font-size: 1.1rem;
    color: #757575;
    min-height: 24px;
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.value-display {
    font-size: 2.5rem;
    font-weight: 600;
    color: #212121;
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.2;
}

.buttons-area {
    flex: 1;
    min-height: 0;
}

@media (max-width: 600px) {
    .value-display {
        font-size: 2rem;
    }

    .calculator-container {
        padding: 12px;
        gap: 12px;
    }
}
</style>

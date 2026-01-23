import { ref } from 'vue'

export type Product = {
    price: string
    quantity: string
    packCount: string
    unitPrice?: string
}
export type CalculationResult = {
    unitPrice: number
    totalQuantity: number
    pricePerUnit: number
}
export type ValidationError = {
    productA: string | null
    productB: string | null
}
export type ProductNameType = 'A' | 'B'
export type FieldType = 'price' | 'quantity' | 'packCount'
type ResultProduct = ProductNameType | 'same'

export const usePriceCalculator = () => {
    // 商品A
    const productA = ref<Product>({
        price: '',
        quantity: '1',
        packCount: '',
    })
    // 商品B
    const productB = ref<Product>({
        price: '',
        quantity: '1',
        packCount: '',
    })
    const resultProduct = ref<ResultProduct>()
    // 現在アクティブな商品とフィールド
    const activeProduct = ref<ProductNameType>('A')
    const activeField = ref<FieldType>('price')
    // キーボード表示状態
    const isKeyboardVisible = ref(false)
    // 計算結果の表示状態
    const showResults = ref(false)
    // バリデーションエラー
    const validationErrors = ref<ValidationError>({
        productA: null,
        productB: null,
    })

    // 入力値を取得
    const getCurrentValue = (): string => {
        const product = activeProduct.value === 'A' ? productA.value : productB.value
        return product[activeField.value]
    }

    // 入力値を設定
    const setCurrentValue = (value: string) => {
        const product = activeProduct.value === 'A' ? productA.value : productB.value
        product[activeField.value] = value
        // 値が変更されたら結果をリセット
        showResults.value = false
        validationErrors.value = { productA: null, productB: null }
    }

    // 数値入力処理
    const inputNumber = (num: string) => {
        let currentVal = getCurrentValue()

        // 小数点の処理
        if (num === '.') {
            if (currentVal.includes('.')) return
            if (currentVal === '') currentVal = '0'
        }

        setCurrentValue(currentVal + num)
    }

    // バックスペース処理
    const backspace = () => {
        const currentVal = getCurrentValue()
        if (currentVal.length > 0) {
            setCurrentValue(currentVal.slice(0, -1))
        }
    }

    // クリア処理
    const clearActive = () => {
        setCurrentValue('')
    }

    // アクティブフィールド設定
    const setActive = (product: ProductNameType, field: FieldType) => {
        activeProduct.value = product
        activeField.value = field
        isKeyboardVisible.value = true
    }

    // 商品のバリデーション
    const validateProduct = (
        product: Product,
        productName: ProductNameType,
    ): { product: ProductNameType; field: FieldType } | null => {
        if (!product.price) {
            return { product: productName, field: 'price' }
        }
        if (!product.quantity) {
            return { product: productName, field: 'quantity' }
        }

        return null
    }

    // 計算実行
    const calculate = (): { product: ProductNameType; field: FieldType } | void => {
        // バリデーション
        const errorA = validateProduct(productA.value, 'A')
        const errorB = validateProduct(productB.value, 'B')

        if (errorA) {
            return errorA
        }

        if (errorB) {
            return errorB
        }

        // 計算実行
        const resultA = calculateUnitPrice(productA.value)
        const resultB = calculateUnitPrice(productB.value)
        if (!resultA || !resultB) return

        isKeyboardVisible.value = false

        productA.value.unitPrice = resultA.unitPrice.toLocaleString()
        productB.value.unitPrice = resultB.unitPrice.toLocaleString()
        if (resultA.pricePerUnit == resultB.pricePerUnit) {
            resultProduct.value = 'same'
        } else {
            resultProduct.value = resultA.pricePerUnit < resultB.pricePerUnit ? 'A' : 'B'
        }
    }

    // 単価計算
    const calculateUnitPrice = (product: Product): CalculationResult | null => {
        const price = parseFloat(product.price)
        const quantity = parseFloat(product.quantity)
        const packCount = product.packCount ? parseFloat(product.packCount) : 1

        if (isNaN(price) || isNaN(quantity) || price <= 0 || quantity <= 0) {
            return null
        }

        // パック数が入力されていない場合は1として計算
        const totalQuantity = quantity * packCount
        const pricePerUnit = price / totalQuantity

        return {
            unitPrice: price,
            totalQuantity,
            pricePerUnit,
        }
    }

    // リセット処理
    const resetAll = () => {
        productA.value = { price: '', quantity: '', packCount: '' }
        productB.value = { price: '', quantity: '', packCount: '' }
        activeProduct.value = 'A'
        activeField.value = 'price'
        showResults.value = false
        validationErrors.value = { productA: null, productB: null }
    }

    const productFormReset = (productName: ProductNameType): void => {
        resultProduct.value = undefined
        if (productName == 'A') {
            productA.value = { price: '', quantity: '1', packCount: '' }
        }
        if (productName == 'B') {
            productB.value = { price: '', quantity: '1', packCount: '' }
        }
    }

    // フォームがアクティブかチェック
    const isFormActive = (product: ProductNameType, field: FieldType): boolean => {
        return activeProduct.value === product && activeField.value === field
    }

    return {
        productA,
        productB,
        resultProduct,
        activeProduct,
        activeField,
        isKeyboardVisible,
        showResults,
        validationErrors,
        productFormReset,
        inputNumber,
        backspace,
        clearActive,
        setActive,
        calculate,
        getCurrentValue,
        resetAll,
        isFieldActive: isFormActive,
    }
}

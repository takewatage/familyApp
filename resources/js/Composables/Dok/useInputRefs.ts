import { useTemplateRef } from 'vue'
import { FieldType, ProductNameType } from '@/composables/dok/usePriceCalculator'

export const useInputRefs = () => {
    const priceARef = useTemplateRef<HTMLElement>('priceA')
    const priceBRef = useTemplateRef<HTMLElement>('priceB')
    const quantityARef = useTemplateRef<HTMLElement>('quantityA')
    const quantityBRef = useTemplateRef<HTMLElement>('quantityB')
    const packCountARef = useTemplateRef<HTMLElement>('packCountA')
    const packCountBRef = useTemplateRef<HTMLElement>('packCountB')

    const formRefs = {
        priceA: priceARef,
        priceB: priceBRef,
        quantityA: quantityARef,
        quantityB: quantityBRef,
        packCountA: packCountARef,
        packCountB: packCountBRef,
    }

    const getRefName = (product: ProductNameType, field: FieldType): string => {
        return `${field}${product}`
    }

    // const focusInput = (product: ProductNameType, field: FieldType): void => {
    //     const refName = getRefName(product, field)
    //     refs[refName]?.value?.focus()
    // }
    //
    // const blurInput = (product: ProductNameType, field: FieldType): void => {
    //     const refName = getRefName(product, field)
    //     refs[refName]?.value?.blur()
    // }

    return {
        formRefs,
        getRefName,
    }
}

/**
 * 金額（数値 or decimal 文字列）を「¥1,234」形式に整形する。
 * @example '1234' → '¥1,234' / 1234 → '¥1,234'
 */
export const formatYen = (amount: string | number | null | undefined): string => {
    if (amount === null || amount === undefined || amount === '') {
        return ''
    }

    const value = Number(amount)

    if (Number.isNaN(value)) {
        return ''
    }

    return `¥${value.toLocaleString()}`
}

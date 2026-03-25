import dayjs from 'dayjs'

/**
 * 日付を「YYYY年M月D日」形式に変換する
 * @example '2000-01-15' → '2000年1月15日'
 */
export const formatDate = (date: string | null | undefined): string => {
    if (!date) {
        return ''
    }

    return dayjs(date).format('YYYY年M月D日')
}

/**
 * 日付を「YYYY年M月D日 HH:mm」形式に変換する
 * @example '2000-01-15T10:30:00' → '2000年1月15日 10:30'
 */
export const formatDateTime = (date: string | null | undefined): string => {
    if (!date) {
        return ''
    }

    return dayjs(date).format('YYYY年M月D日 HH:mm')
}

/**
 * 日付を「M月D日」形式に変換する
 * @example '2000-01-15' → '1月15日'
 */
export const formatDateShort = (date: string | null | undefined): string => {
    if (!date) {
        return ''
    }

    return dayjs(date).format('M月D日')
}

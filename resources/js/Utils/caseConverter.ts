/* eslint-disable @typescript-eslint/no-explicit-any */

/**
 * camelCaseをsnake_caseに変換
 */
export function toSnakeCase(str: string): string {
    return str.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`)
}

/**
 * snake_caseをcamelCaseに変換
 */
export function toCamelCase(str: string): string {
    return str.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase())
}

/**
 * オブジェクトのキーをcamelCaseからsnake_caseに変換（deep対応）
 */
export function keysToSnake<T extends Record<string, any>>(obj: T): Record<string, any> {
    if (obj === null || typeof obj !== 'object') {
        return obj
    }

    if (Array.isArray(obj)) {
        return obj.map((item) => keysToSnake(item))
    }

    const result: Record<string, any> = {}
    for (const key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) {
            const value = obj[key]
            const snakeKey = toSnakeCase(key)

            if (value !== null && typeof value === 'object') {
                result[snakeKey] = keysToSnake(value)
            } else {
                result[snakeKey] = value
            }
        }
    }
    return result
}

/**
 * オブジェクトのキーをsnake_caseからcamelCaseに変換（deep対応）
 */
export function keysToCamel<T extends Record<string, any>>(obj: T): Record<string, any> {
    if (obj === null || typeof obj !== 'object') {
        return obj
    }

    if (Array.isArray(obj)) {
        return obj.map((item) => keysToCamel(item))
    }

    const result: Record<string, any> = {}
    for (const key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) {
            const value = obj[key]
            const camelKey = toCamelCase(key)

            if (value !== null && typeof value === 'object') {
                result[camelKey] = keysToCamel(value)
            } else {
                result[camelKey] = value
            }
        }
    }
    return result
}

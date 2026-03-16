/* eslint-disable @typescript-eslint/no-explicit-any */

/**
 * 再帰的にキー変換すべきプレーンオブジェクトかどうか判定
 */
function isPlainObject(val: any): val is Record<string, any> {
    return (
        val !== null &&
        typeof val === 'object' &&
        !Array.isArray(val) &&
        !(val instanceof File) &&
        !(val instanceof Blob) &&
        !(val instanceof Date) &&
        !(val instanceof FormData)
    )
}


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
export function keysToSnake(obj: any): any {
    if (Array.isArray(obj)) {
        return obj.map(keysToSnake)
    }
    if (isPlainObject(obj)) {
        const result: Record<string, any> = {}
        for (const key in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, key)) {
                result[toSnakeCase(key)] = keysToSnake(obj[key])
            }
        }
        return result
    }
    // File, Blob, Date, プリミティブ等はそのまま
    return obj
}

/**
 * オブジェクトのキーをsnake_caseからcamelCaseに変換（deep対応）
 */
export function keysToCamel(obj: any): any {
    if (Array.isArray(obj)) {
        return obj.map(keysToCamel)
    }
    if (isPlainObject(obj)) {
        const result: Record<string, any> = {}
        for (const key in obj) {
            if (Object.prototype.hasOwnProperty.call(obj, key)) {
                result[toCamelCase(key)] = keysToCamel(obj[key])
            }
        }
        return result
    }
    return obj
}

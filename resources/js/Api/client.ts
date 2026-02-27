import axios, { type AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'

// =============================================================================
// Types
// =============================================================================

/**
 * Laravel のバリデーションエラーレスポンス形式
 */
export interface ValidationErrors {
    message: string
    errors: Record<string, string[]>
}

/**
 * Laravel の一般的なエラーレスポンス形式
 */
export interface ApiErrorResponse {
    message: string
    errors?: Record<string, string[]>
}

/**
 * API エラーをラップしたカスタムエラー
 */
export class ApiError extends Error {
    public readonly status: number
    public readonly validationErrors: Record<string, string[]>
    public readonly isValidationError: boolean

    constructor(error: AxiosError<ApiErrorResponse>) {
        const message = error.response?.data?.message || error.message
        super(message)
        this.name = 'ApiError'
        this.status = error.response?.status || 0
        this.validationErrors = error.response?.data?.errors || {}
        this.isValidationError = this.status === 422
    }

    /**
     * 特定フィールドのエラーメッセージ一覧を取得
     */
    getFieldErrors(field: string): string[] {
        return this.validationErrors[field] || []
    }

    /**
     * 特定フィールドの最初のエラーメッセージを取得
     */
    getFirstFieldError(field: string): string | undefined {
        return this.validationErrors[field]?.[0]
    }

    /**
     * 全フィールドのエラーをフラットな配列で取得
     */
    getAllErrors(): string[] {
        return Object.values(this.validationErrors).flat()
    }
}

// =============================================================================
// ケース変換ユーティリティ
// =============================================================================

/**
 * スネークケース → キャメルケースに変換
 */
function snakeToCamel(str: string): string {
    return str.replace(/_([a-z])/g, (_, char) => char.toUpperCase())
}

/**
 * オブジェクトのキーを再帰的にスネーク → キャメルケースに変換
 */
function convertKeysToCamel<T>(data: unknown): T {
    if (Array.isArray(data)) {
        return data.map((item) => convertKeysToCamel(item)) as T
    }

    if (
        data !== null &&
        typeof data === 'object' &&
        !(data instanceof File) &&
        !(data instanceof Blob) &&
        !(data instanceof Date)
    ) {
        return Object.fromEntries(
            Object.entries(data as Record<string, unknown>).map(([key, value]) => [snakeToCamel(key), convertKeysToCamel(value)]),
        ) as T
    }

    return data as T
}

// =============================================================================
// トースト通知（抽象化レイヤー）
// =============================================================================

/**
 * トースト通知のインターフェース
 * 使用するライブラリに合わせて実装を差し替える
 *
 * 例: vue-toastification, vue-sonner, naive-ui など
 */
interface ToastHandler {
    success: (message: string) => void
    error: (message: string) => void
    warning: (message: string) => void
}

/**
 * デフォルトのトーストハンドラー（console にフォールバック）
 * 実際のライブラリが決まったら setToastHandler() で差し替える
 */
let toast: ToastHandler = {
    success: (message) => console.log('[Toast Success]', message),
    error: (message) => console.error('[Toast Error]', message),
    warning: (message) => console.warn('[Toast Warning]', message),
}

/**
 * トーストハンドラーを設定する
 *
 * @example
 * // vue-toastification の場合
 * import { useToast } from 'vue-toastification';
 * setToastHandler(useToast());
 *
 * @example
 * // vue-sonner の場合
 * import { toast } from 'vue-sonner';
 * setToastHandler({
 *   success: toast.success,
 *   error: toast.error,
 *   warning: toast.warning,
 * });
 */
export function setToastHandler(handler: ToastHandler): void {
    toast = handler
}

// =============================================================================
// HTTP ステータス別メッセージ
// =============================================================================

const ERROR_MESSAGES: Record<number, string> = {
    401: '認証が必要です。ログインし直してください。',
    403: 'この操作を行う権限がありません。',
    404: 'リソースが見つかりませんでした。',
    419: 'セッションの有効期限が切れました。ページを再読み込みしてください。',
    422: '入力内容にエラーがあります。',
    429: 'リクエストが多すぎます。しばらく時間をおいてから再度お試しください。',
    500: 'サーバーエラーが発生しました。',
    503: '現在メンテナンス中です。しばらくお待ちください。',
}

function getErrorMessage(status: number): string {
    return ERROR_MESSAGES[status] || '予期しないエラーが発生しました。'
}

// =============================================================================
// Axios インスタンス
// =============================================================================

const client = axios.create({
    baseURL: '/api',
    timeout: 30_000,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
})

// =============================================================================
// リクエストインターセプター
// =============================================================================

client.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
        // FormData の場合は Content-Type を削除して axios に自動設定させる
        if (config.data instanceof FormData) {
            delete config.headers['Content-Type']
        }

        return config
    },
    (error) => Promise.reject(error),
)

// =============================================================================
// レスポンスインターセプター
// =============================================================================

client.interceptors.response.use(
    (response: AxiosResponse) => {
        // レスポンスデータのキーをキャメルケースに変換
        if (response.data) {
            response.data = convertKeysToCamel(response.data)
        }
        return response
    },
    (error: AxiosError<ApiErrorResponse>) => {
        const status = error.response?.status

        // ネットワークエラー（レスポンスなし）
        if (!error.response) {
            toast.error('ネットワークエラーが発生しました。接続を確認してください。')
            return Promise.reject(new ApiError(error))
        }

        // 認証切れ → ログインページへリダイレクト
        if (status === 401) {
            toast.error(getErrorMessage(401))
            window.location.href = '/login'
            return Promise.reject(new ApiError(error))
        }

        // CSRF トークン期限切れ → ページリロード
        if (status === 419) {
            toast.warning(getErrorMessage(419))
            window.location.reload()
            return Promise.reject(new ApiError(error))
        }

        // バリデーションエラー → トースト表示（詳細は呼び出し側で処理）
        if (status === 422) {
            toast.error(getErrorMessage(422))
            return Promise.reject(new ApiError(error))
        }

        // レート制限
        if (status === 429) {
            toast.warning(getErrorMessage(429))
            return Promise.reject(new ApiError(error))
        }

        // その他のエラー
        if (status) {
            toast.error(error.response.data?.message || getErrorMessage(status))
        }

        return Promise.reject(new ApiError(error))
    },
)

export default client

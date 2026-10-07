import type { APIRequestContext } from '@playwright/test'

export type CreatedFamily = {
    owner: { email: string; password: string; name: string }
    family: { id: string; name: string; code: string }
    inviteUrls: { parent: string; child: string; guest: string }
}

/**
 * E2E 用エンドポイント（routes/e2e.php）で家族とオーナーを作成する
 */
export async function createFamily(
    request: APIRequestContext,
    options: {
        ownerEmail?: string
        maxMembers?: number
        members?: number
        expired?: boolean
    } = {},
): Promise<CreatedFamily> {
    const response = await request.post('/__e2e/families', {
        data: {
            owner_email: options.ownerEmail,
            max_members: options.maxMembers,
            members: options.members,
            expired: options.expired,
        },
    })

    if (!response.ok()) {
        throw new Error(
            `家族の作成に失敗しました: ${response.status()} ${await response.text()}`,
        )
    }

    return response.json()
}

/** テストごとに一意なメールアドレス */
export function uniqueEmail(prefix = 'e2e'): string {
    return `${prefix}-${Date.now()}-${Math.floor(Math.random() * 10000)}@example.com`
}

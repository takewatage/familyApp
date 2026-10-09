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
        /** オーナーの誕生日を今日にする（それ以外のユーザーは誕生日なし） */
        ownerBirthdayToday?: boolean
    } = {},
): Promise<CreatedFamily> {
    const response = await request.post('/__e2e/families', {
        data: {
            owner_email: options.ownerEmail,
            max_members: options.maxMembers,
            members: options.members,
            expired: options.expired,
            owner_birthday_today: options.ownerBirthdayToday,
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

/**
 * E2E 用エンドポイントで家族の予定を作成する
 */
export async function createCalendarEvent(
    request: APIRequestContext,
    familyId: string,
    event: {
        title: string
        /** 今日から何日後に始まるか（既定 0 = 今日） */
        startOffset?: number
        /** 日数（既定 1） */
        days?: number
        startTime?: string
        rrule?: string
        /** 家族メンバー全員を参加者にする */
        participants?: boolean
    },
): Promise<string> {
    const response = await request.post('/__e2e/calendar-events', {
        data: {
            family_id: familyId,
            title: event.title,
            start_offset: event.startOffset,
            days: event.days,
            start_time: event.startTime,
            rrule: event.rrule,
            participants: event.participants,
        },
    })

    if (!response.ok()) {
        throw new Error(`予定の作成に失敗しました: ${response.status()} ${await response.text()}`)
    }

    return (await response.json()).id
}

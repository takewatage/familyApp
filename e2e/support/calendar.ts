import { expect, type APIRequestContext, type Page } from '@playwright/test'
import { createFamily, type CreatedFamily } from './factory'
import { login } from './auth'

/**
 * 家族を作ってログインし、カレンダー画面を開く（予定の読み込み完了まで待つ）
 */
export async function openCalendar(
    page: Page,
    request: APIRequestContext,
    options: Parameters<typeof createFamily>[1] = {},
    beforeOpen?: (family: CreatedFamily) => Promise<void>,
): Promise<CreatedFamily> {
    const family = await createFamily(request, options)

    await beforeOpen?.(family)
    await login(page, family.owner.email, family.owner.password)
    await expect(page).toHaveURL(/\/home$/)
    await gotoCalendar(page)

    return family
}

export async function gotoCalendar(page: Page): Promise<void> {
    const loaded = page.waitForResponse((res) => res.url().includes('/calendar/events') && res.request().method() === 'GET')

    await page.goto('/calendar')
    await loaded
    await expect(page.getByRole('progressbar', { name: '予定を読み込み中' })).toBeHidden()
}

/**
 * 表示中の月の「今日」のマス。
 * スワイプ用に前月・当月・翌月の 3 ページを並べており（中央が表示中）、各月は 6 週で表示するため、
 * 前後の月のページにも当月外のマスとして今日が出ることがある。表示中の月の当月セルに限定する
 */
export function todayCell(page: Page) {
    return page.locator('.month-grid').nth(1).locator('.day-cell:not(.day-cell--other) .day-cell__num--today')
}

/** 今日の日付をタップして、日別の予定一覧（全画面）を開く */
export async function openToday(page: Page) {
    await todayCell(page).click()

    const dialog = page.locator('.v-dialog--fullscreen')

    await expect(dialog).toBeVisible()

    return dialog
}

/** 保存・削除の API が終わり、予定を取り直すまで待つ */
export async function waitForReload(page: Page, action: () => Promise<void>): Promise<void> {
    const reloaded = page.waitForResponse((res) => res.url().includes('/calendar/events') && res.request().method() === 'GET')

    await action()
    await reloaded
}

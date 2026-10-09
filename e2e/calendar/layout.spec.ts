import { expect, test } from '@playwright/test'
import { createCalendarEvent } from '../support/factory'
import { openCalendar } from '../support/calendar'

// カレンダー: 月によって 5 週・6 週と行数が変わっても、カレンダーの高さは変わらない（常に 6 週で表示）
test('月を移動してもカレンダーの高さが変わらない', async ({ page, request }) => {
    await openCalendar(page, request)

    const viewport = page.locator('.swipe-calendar__viewport')
    const heightOf = () => viewport.evaluate((el) => (el as HTMLElement).offsetHeight)
    const first = await heightOf()

    // 前後 3 か月ずつ移動して、高さと行数（6 週 = 42 マス）を確認する
    for (const direction of ['left', 'left', 'left', 'right', 'right', 'right', 'right', 'right', 'right']) {
        await page.locator(`button:has(.mdi-chevron-${direction})`).filter({ visible: true }).first().click()
        await page.waitForTimeout(400)

        expect(await heightOf()).toBe(first)
        await expect(page.locator('.month-grid').nth(1).locator('.day-cell')).toHaveCount(42)
    }
})

test('長いタイトルの予定があっても、セルの幅は変わらない（文字は切れる）', async ({ page, request }) => {
    await openCalendar(page, request, {}, async ({ family }) => {
        await createCalendarEvent(request, family.id, { title: 'とても長いタイトルの予定とても長いタイトルの予定とても長いタイトル' })
    })

    // 表示中の月（中央のページ）の 1 行目 7 マスの幅がすべて同じ
    const widths = await page
        .locator('.month-grid')
        .nth(1)
        .locator('.day-cell')
        .evaluateAll((cells) => cells.slice(0, 7).map((c) => Math.round((c as HTMLElement).getBoundingClientRect().width)))
    expect(new Set(widths).size).toBe(1)

    // 予定の行の幅も他の行と同じ（今日の行を含む全行）
    const allWidths = await page
        .locator('.month-grid')
        .nth(1)
        .locator('.day-cell')
        .evaluateAll((cells) => cells.map((c) => Math.round((c as HTMLElement).getBoundingClientRect().width)))
    expect(Math.max(...allWidths) - Math.min(...allWidths)).toBeLessThanOrEqual(1)
})

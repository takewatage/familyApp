import { expect, test } from '@playwright/test'
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

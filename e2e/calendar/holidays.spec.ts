import { expect, test, type Page } from '@playwright/test'
import { createCalendarEvent } from '../support/factory'
import { gotoCalendar, openCalendar, openToday, todayCell, waitForReload } from '../support/calendar'

/** 表示中の月（中央のページ）の当月のマス */
function dayCell(page: Page, day: number) {
    return page
        .locator('.month-grid')
        .nth(1)
        .locator('.day-cell:not(.day-cell--other)')
        .filter({ has: page.locator('.day-cell__num', { hasText: new RegExp(`^${day}$`) }) })
}

// 日本の祝日・休日の表示（祝日はサーバーで計算する。画面の「今日」を祝日の 2026/11/3（文化の日）に固定する）
test.describe('カレンダーの祝日', () => {
    test.beforeEach(async ({ page }) => {
        await page.clock.setFixedTime(new Date('2026-11-03T10:00:00'))
    })

    test('祝日の日付が日曜と同じ色になり、祝日名が表示される（今日の表示を優先）', async ({ page, request }) => {
        // 祝日（11/3）をまたぐ複数日の予定があっても、祝日はその日の先頭に表示する
        await openCalendar(page, request, {}, async ({ family }) => {
            await createCalendarEvent(request, family.id, { title: '旅行', startDate: '2026-11-01', days: 4 })
        })

        // 11/23 勤労感謝の日（月曜）: 数字が日曜（11/1）と同じ色、祝日名のチップ
        const labor = dayCell(page, 23)
        await expect(labor).toHaveClass(/day-cell--holiday/)
        await expect(labor.locator('.day-cell__event--holiday')).toHaveText('勤労感謝の日')
        const sundayColor = await dayCell(page, 1).locator('.day-cell__num').evaluate((el) => getComputedStyle(el).color)
        await expect(labor.locator('.day-cell__num')).toHaveCSS('color', sundayColor)

        // 平日（11/24）は祝日にならない
        await expect(dayCell(page, 24)).not.toHaveClass(/day-cell--holiday/)

        // 今日（11/3 文化の日）は今日の表示（黒丸）のまま、祝日名は表示される
        await expect(todayCell(page)).toHaveText('3')
        await expect(dayCell(page, 3).locator('.day-cell__event--holiday')).toHaveText('文化の日')
        await expect(dayCell(page, 3).locator('.day-cell__event').first()).toHaveText('文化の日')
        await expect(page.locator('.upcoming-event').first()).toContainText('文化の日')

        // 「これから7日間の予定」にも祝日を表示する
        await expect(page.locator('.upcoming-event', { hasText: '文化の日' })).toBeVisible()
    })

    test('祝日をタップしても編集画面は開かない', async ({ page, request }) => {
        await openCalendar(page, request)

        const dialog = await openToday(page)
        await dialog.getByText('文化の日').click()

        // 編集画面は開くまでに少しかかるため、開く余地を与えてから開いていないことを確かめる
        await page.waitForTimeout(1000)
        // （予定をタップすると日別の予定一覧は閉じる。誕生日と同じ動き）
        await expect(page.getByText('予定を編集')).toHaveCount(0)
    })

    test('カレンダー設定で祝日を非表示にでき、再読み込みしても残る', async ({ page, request }) => {
        await openCalendar(page, request)
        await expect(dayCell(page, 23)).toHaveClass(/day-cell--holiday/)

        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        const toggle = page.getByRole('checkbox', { name: '祝日を表示' })
        await expect(toggle).toBeChecked()

        await waitForReload(page, () => page.getByText('祝日を表示', { exact: true }).click())
        await expect(toggle).not.toBeChecked()
        await page.keyboard.press('Escape')

        // 祝日名・祝日の色が消える（日曜の色分けは残る）
        await expect(page.locator('.day-cell__event--holiday')).toHaveCount(0)
        await expect(page.locator('.day-cell--holiday')).toHaveCount(0)
        await expect(dayCell(page, 1)).toHaveClass(/day-cell--sun/)
        await expect(page.locator('.upcoming-event', { hasText: '文化の日' })).toHaveCount(0)

        // 再読み込みしても非表示のまま
        await gotoCalendar(page)
        await expect(dayCell(page, 3)).toBeVisible()
        await expect(page.locator('.day-cell__event--holiday')).toHaveCount(0)
        await expect(dayCell(page, 23)).not.toHaveClass(/day-cell--holiday/)

        // 表示に戻す
        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        await waitForReload(page, () => page.getByText('祝日を表示', { exact: true }).click())
        await page.keyboard.press('Escape')
        await expect(dayCell(page, 23).locator('.day-cell__event--holiday')).toHaveText('勤労感謝の日')
    })
})

import { expect, test } from '@playwright/test'
import { createCalendarEvent } from '../support/factory'
import { gotoCalendar, openCalendar, waitForReload } from '../support/calendar'

// カレンダー: 予定のラベル（選択・名前とカラーの編集。家族で共有し保存される）
test.describe('カレンダーのラベル', () => {
    test('ボトムシートでラベルを選んで予定を追加できる', async ({ page, request }) => {
        await openCalendar(page, request)

        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('ラベルの予定')

        await page.getByRole('button', { name: 'ラベルを選択' }).click()
        await page.getByRole('radio', { name: 'ディープ・スカイブルー' }).click()
        await expect(page.getByRole('button', { name: 'ラベルを選択' }).locator('input')).toHaveValue(new RegExp('ディープ・スカイブルー'))

        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())

        // 予定の色がラベルのカラー（#2E8FE0）になる
        const item = page.locator('.upcoming-event', { hasText: 'ラベルの予定' })
        await expect(item).toHaveCSS('border-left-color', 'rgb(46, 143, 224)')
    })

    test('ラベル名とカラーを変更すると保存され、選択肢と予定の色に反映される', async ({ page, request }) => {
        await openCalendar(page, request, {}, async ({ family }) => {
            // ラベルなしの予定は 1 つ目のラベルではなく既定色になるため、ラベル付きで作る（作成後に画面で設定）
            await createCalendarEvent(request, family.id, { title: '買い出し' })
        })

        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('button', { name: 'ラベルを選択' }).click()
        await page.getByRole('button', { name: 'ラベル名やカラーを変更' }).click()

        // 1 つ目のラベル（エメラルド・グリーン）を「家族」・ブルーに変更
        await expect(page.getByText('ラベルの編集')).toBeVisible()
        await page.getByRole('textbox', { name: 'ラベル名' }).first().fill('家族')
        await page.getByRole('button', { name: /のカラーを変更$/ }).first().click()
        await page.getByRole('radio', { name: 'ブルー', exact: true }).click()

        const saved = page.waitForResponse((res) => res.url().includes('/calendar/labels') && res.request().method() === 'PUT')
        // 予定フォームの上に重なっているラベル編集画面の「保存」
        await page.getByRole('button', { name: '保存' }).last().click()
        expect((await saved).ok()).toBe(true)

        // 予定フォームのラベル欄（既定は 1 つ目のラベル）に新しい名前が出る
        await expect(page.getByRole('button', { name: 'ラベルを選択' }).locator('input')).toHaveValue(new RegExp('家族'))

        // このラベルで予定を保存すると、新しいカラー（#1e88e5）で表示される
        await page.getByRole('textbox', { name: /タイトル/ }).fill('家族の予定')
        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())
        await expect(page.locator('.upcoming-event', { hasText: '家族の予定' })).toHaveCSS('border-left-color', 'rgb(30, 136, 229)')

        // 再読み込みしてもラベルの変更は残っている（家族で共有して保存される）
        await gotoCalendar(page)
        await page.getByRole('button', { name: '予定を追加' }).click()
        await expect(page.getByRole('button', { name: 'ラベルを選択' }).locator('input')).toHaveValue(new RegExp('家族'))
    })
})

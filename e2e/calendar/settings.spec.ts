import { expect, test } from '@playwright/test'
import { gotoCalendar, openCalendar } from '../support/calendar'

// カレンダー設定（歯車 → ボトムシート）: 誕生日のラベル・ラベルの編集
test.describe('カレンダー設定', () => {
    test('誕生日のカラーをカラーピッカーで選ぶと誕生日がその色になり、保存される', async ({ page, request }) => {
        const { owner } = await openCalendar(page, request, { ownerBirthdayToday: true })
        const birthday = page.locator('.upcoming-event', { hasText: `${owner.name}の誕生日` })

        // 未設定は既定の色（#F79A2E）
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(247, 154, 46)')

        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        await expect(page.getByText('カレンダー設定', { exact: true })).toBeVisible()
        await page.getByText('誕生日のカラー').click()

        // カラーピッカーの HEX 入力欄に色を入れて決定する
        const sheet = page.locator('.birthday-color-sheet')
        const hex = sheet.locator('.v-color-picker-edit input')
        await hex.fill('#123ABC')
        await hex.press('Enter')

        const saved = page.waitForResponse((res) => res.url().includes('/calendar/settings') && res.request().method() === 'PUT')
        await sheet.getByRole('button', { name: '決定' }).click()
        expect((await saved).ok()).toBe(true)

        // 誕生日がその色になり、再読み込みしても残る
        await page.keyboard.press('Escape')
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(18, 58, 188)')
        await gotoCalendar(page)
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(18, 58, 188)')

        // 「既定の色に戻す」で元の色
        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        await page.getByText('誕生日のカラー').click()
        await page.locator('.birthday-color-sheet').getByRole('button', { name: '既定の色に戻す' }).click()
        await page.keyboard.press('Escape')
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(247, 154, 46)')
    })

    test('設定からラベルの名前を変更できる', async ({ page, request }) => {
        await openCalendar(page, request)

        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        await page.getByText('ラベルの編集').click()
        await expect(page.getByText('ラベルの編集').last()).toBeVisible()
        await page.getByRole('textbox', { name: 'ラベル名' }).first().fill('家族の予定')

        const saved = page.waitForResponse((res) => res.url().includes('/calendar/labels') && res.request().method() === 'PUT')
        await page.getByRole('button', { name: '保存' }).click()
        expect((await saved).ok()).toBe(true)

        // 予定フォームのラベル欄（既定は 1 つ目のラベル）に反映されている
        await page.keyboard.press('Escape')
        await page.getByRole('button', { name: '予定を追加' }).click()
        await expect(page.getByRole('button', { name: 'ラベルを選択' }).locator('input')).toHaveValue('家族の予定')
    })
})

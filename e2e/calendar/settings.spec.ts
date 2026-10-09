import { expect, test } from '@playwright/test'
import { gotoCalendar, openCalendar } from '../support/calendar'

// カレンダー設定（歯車 → ボトムシート）: 誕生日のラベル・ラベルの編集
test.describe('カレンダー設定', () => {
    test('誕生日のラベルを設定すると誕生日がその色になり、保存される', async ({ page, request }) => {
        const { owner } = await openCalendar(page, request, { ownerBirthdayToday: true })
        const birthday = page.locator('.upcoming-event', { hasText: `${owner.name}の誕生日` })

        // 未設定は既定の色（#F79A2E）
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(247, 154, 46)')

        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        await expect(page.getByText('カレンダー設定', { exact: true })).toBeVisible()
        await expect(page.getByText('ラベルの編集')).toBeVisible()
        await page.getByText('誕生日のラベル').click()

        const saved = page.waitForResponse((res) => res.url().includes('/calendar/settings') && res.request().method() === 'PUT')
        await page.getByRole('radio', { name: 'ディープ・スカイブルー' }).click()
        expect((await saved).ok()).toBe(true)

        // 誕生日がラベルの色（#2E8FE0）になり、再読み込みしても残る
        await page.keyboard.press('Escape')
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(46, 143, 224)')
        await gotoCalendar(page)
        await expect(birthday).toHaveCSS('border-left-color', 'rgb(46, 143, 224)')

        // 「ラベルを使わない」に戻すと既定の色
        await page.getByRole('button', { name: 'カレンダー設定' }).click()
        await page.getByText('誕生日のラベル').click()
        await page.getByRole('radio', { name: /ラベルを使わない/ }).click()
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

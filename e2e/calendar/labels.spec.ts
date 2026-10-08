import { expect, test } from '@playwright/test'
import { createFamily } from '../support/factory'
import { login } from '../support/auth'

// カレンダー（モックアップ）: 予定のラベル（選択・名前とカラーの編集）
test.describe('カレンダーのラベル', () => {
    test.beforeEach(async ({ page, request }) => {
        const { owner } = await createFamily(request)
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/calendar')
    })

    test('ボトムシートでラベルを選んで予定を追加できる', async ({ page }) => {
        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('ラベルの予定')

        await page.getByRole('button', { name: 'ラベルを選択' }).click()
        await page.getByRole('radio', { name: 'ディープ・スカイブルー' }).click()
        await expect(page.getByRole('button', { name: 'ラベルを選択' })).toContainText('ディープ・スカイブルー')

        await page.getByRole('button', { name: '保存' }).click()

        // 予定の色がラベルのカラー（#2E8FE0）になる
        const item = page.locator('.upcoming-event', { hasText: 'ラベルの予定' })
        await expect(item).toHaveCSS('border-left-color', 'rgb(46, 143, 224)')
    })

    test('ラベル名とカラーを変更すると、選択肢と予定の色に反映される', async ({ page }) => {
        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('button', { name: 'ラベルを選択' }).click()
        await page.getByRole('button', { name: 'ラベル名やカラーを変更' }).click()

        // 1 つ目のラベル（エメラルド・グリーン）を「家族」・ブルーに変更
        await expect(page.getByText('ラベルの編集')).toBeVisible()
        const nameField = page.getByRole('textbox', { name: 'ラベル名' }).first()
        await nameField.fill('家族')
        await page.getByRole('button', { name: /のカラーを変更$/ }).first().click()
        await page.getByRole('radio', { name: 'ブルー', exact: true }).click()
        // 予定フォームの上に重なっているラベル編集画面の「保存」
        await page.getByRole('button', { name: '保存' }).last().click()

        // 予定フォームのラベル欄（既定は 1 つ目のラベル）に新しい名前が出る
        await expect(page.getByRole('button', { name: 'ラベルを選択' })).toContainText('家族')

        // ダイアログを閉じると、1 つ目のラベルの予定（買い出し）が新しいカラー（#1e88e5）になる
        await page.keyboard.press('Escape')
        const item = page.locator('.upcoming-event', { hasText: '買い出し' })
        await expect(item).toHaveCSS('border-left-color', 'rgb(30, 136, 229)')
    })
})

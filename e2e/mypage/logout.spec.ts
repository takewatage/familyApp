import { expect, test } from '@playwright/test'
import { createFamily } from '../support/factory'
import { login } from '../support/auth'

test('マイページの一番下からログアウトできる', async ({ page, request }) => {
    const { owner } = await createFamily(request)
    await login(page, owner.email, owner.password)
    await expect(page).toHaveURL(/\/home$/)
    await page.goto('/mypage')

    // マイページのボタンと確認ダイアログのボタンが同じ名前なので、場所で分ける
    const pageButton = page.locator('.v-container').getByRole('button', { name: 'ログアウト' })
    const dialog = page.locator('.v-overlay--active', { hasText: 'ログアウトしますか？' })

    await pageButton.click()
    await expect(dialog).toBeVisible()
    // 本文は出さない（英語の既定文「Are you sure?」が出ていた不具合の回帰確認）
    await expect(dialog).not.toContainText('Are you sure?')

    // キャンセルするとマイページのまま
    await dialog.getByRole('button', { name: 'キャンセル' }).click()
    await expect(dialog).toBeHidden()
    await expect(page).toHaveURL(/\/mypage$/)

    await pageButton.click()
    await dialog.getByRole('button', { name: 'ログアウト' }).click()

    // ログアウト後はログインが必要なページに入れない
    await page.waitForURL((url) => !url.pathname.startsWith('/mypage'))
    await page.goto('/mypage')
    await expect(page).toHaveURL(/\/login$/)
})

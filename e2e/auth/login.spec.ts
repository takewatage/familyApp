import { expect, test } from '@playwright/test'
import { createFamily } from '../support/factory'
import { expectOnHome, login } from '../support/auth'

// testing.md ケース 1・2・13（ログイン画面）
test.describe('ログイン', () => {
    test('家族コードなしでメール・パスワードでログインできる', async ({
        page,
        request,
    }) => {
        const { owner, family } = await createFamily(request)

        await page.goto('/login')
        await expect(page.getByLabel(/家族コード/)).toHaveCount(0)

        await login(page, owner.email, owner.password)

        await expectOnHome(page, family.name)
    })

    test('パスワードが誤っているとログインできない', async ({
        page,
        request,
    }) => {
        const { owner } = await createFamily(request)

        await login(page, owner.email, 'wrong-password')

        await expect(page).toHaveURL(/\/login$/)
        await expect(
            page.getByText('ログイン情報が存在しません。'),
        ).toBeVisible()
    })

    test('招待がなければ新規登録リンクを表示しない', async ({ page }) => {
        await page.goto('/login')

        await expect(
            page.getByRole('link', { name: 'パスワードをお忘れですか？' }),
        ).toBeVisible()
        await expect(page.getByRole('link', { name: /新規登録/ })).toHaveCount(
            0,
        )
    })
})

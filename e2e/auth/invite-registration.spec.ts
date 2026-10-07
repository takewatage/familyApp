import { expect, test } from '@playwright/test'
import { createFamily, uniqueEmail } from '../support/factory'
import { expectOnHome, fillRegisterForm } from '../support/auth'

// testing.md ケース 5・6・14・15（新規登録は招待経由のみ）
test.describe('招待経由の新規登録', () => {
    test('招待なしで /register を開くとログイン画面に戻される', async ({
        page,
    }) => {
        await page.goto('/register')

        await expect(page).toHaveURL(/\/login$/)
        await expect(
            page.getByText('新規登録は家族からの招待リンクからのみ行えます。'),
        ).toBeVisible()
    })

    test('招待URLから新規登録すると招待先の家族に参加する', async ({
        page,
        request,
    }) => {
        const { family, inviteUrls } = await createFamily(request)

        await page.goto(inviteUrls.parent)
        await expect(page.getByText(family.name)).toBeVisible()
        await page.getByRole('link', { name: '新規登録して参加' }).click()

        await expect(
            page.getByText(`${family.name} への参加登録`),
        ).toBeVisible()
        await fillRegisterForm(page, {
            name: 'E2E太郎',
            email: uniqueEmail(),
            password: 'password123',
        })
        await page.getByRole('button', { name: '登録して参加する' }).click()

        await expectOnHome(page, family.name)
    })

    test('招待ページから「ログインして参加」に進んだ未登録ユーザーも登録できる', async ({
        page,
        request,
    }) => {
        const { family, inviteUrls } = await createFamily(request)

        await page.goto(inviteUrls.guest)
        await page.getByRole('link', { name: 'ログインして参加' }).click()

        await expect(page).toHaveURL(/\/login$/)
        await page.getByRole('link', { name: /新規登録して参加/ }).click()

        await expect(
            page.getByText(`${family.name} への参加登録`),
        ).toBeVisible()
        await fillRegisterForm(page, {
            name: 'E2E花子',
            email: uniqueEmail(),
            password: 'password123',
        })
        await page.getByRole('button', { name: '登録して参加する' }).click()

        await expectOnHome(page, family.name)
    })

    test('期限切れの招待では登録できない', async ({ page, request }) => {
        const { inviteUrls } = await createFamily(request, { expired: true })

        await page.goto(inviteUrls.guest)
        await page.goto('/register')

        await expect(page).toHaveURL(/\/login$/)
        await expect(
            page.getByText('新規登録は家族からの招待リンクからのみ行えます。'),
        ).toBeVisible()
    })

    test('定員に達した家族の招待では登録できない', async ({
        page,
        request,
    }) => {
        const { inviteUrls } = await createFamily(request, { maxMembers: 1 })

        await page.goto(inviteUrls.guest)
        await expect(
            page.getByText('この家族は定員に達しています'),
        ).toBeVisible()
        await page.goto('/register')

        await expect(page).toHaveURL(/\/login$/)
        await expect(
            page.getByText(
                '招待先の家族が定員に達しているため登録できません。',
            ),
        ).toBeVisible()
        await expect(page.getByRole('link', { name: /新規登録/ })).toHaveCount(
            0,
        )
    })
})

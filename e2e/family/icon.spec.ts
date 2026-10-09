import { expect, test } from '@playwright/test'
import { createFamily } from '../support/factory'
import { login } from '../support/auth'

// 正方形の単色 SVG。画像ストレージを使わずに家族のアイコンの表示を確認する
const ICON = `data:image/svg+xml,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256"><rect width="256" height="256" fill="#e53935"/></svg>',
)}`

test.describe('家族のアイコン', () => {
    test('設定したアイコンがサイドメニューの家族名の横に表示される', async ({ page, request }) => {
        const { owner, family } = await createFamily(request, { iconUrl: ICON })
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)

        await page.locator('.v-app-bar button').first().click()
        const drawer = page.locator('.v-navigation-drawer')
        await expect(drawer.getByText(family.name)).toBeVisible()
        await expect(drawer.locator('.drawer-family-icon')).toBeVisible()
    })

    test('アイコンが未設定ならサイドメニューは既定のアイコン', async ({ page, request }) => {
        const { owner } = await createFamily(request)
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)

        await page.locator('.v-app-bar button').first().click()
        const drawer = page.locator('.v-navigation-drawer')
        await expect(drawer.locator('.mdi-account-group').first()).toBeVisible()
        await expect(drawer.locator('.drawer-family-icon')).toHaveCount(0)
    })

    test('家族設定でアイコンのプレビューと変更・削除のボタンが表示される（オーナー）', async ({ page, request }) => {
        const { owner } = await createFamily(request, { iconUrl: ICON })
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/family/settings')

        const card = page.locator('.v-card', { hasText: '家族のアイコン' })
        await expect(card.locator('.image-preview--icon .v-img')).toBeVisible()
        await expect(card.getByRole('button', { name: '画像を選択', exact: true })).toBeVisible()
        await expect(card.getByRole('button', { name: '削除', exact: true })).toBeVisible()
        await expect(card.getByRole('button', { name: '保存' })).toBeDisabled()
    })
})

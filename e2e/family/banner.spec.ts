import { expect, test } from '@playwright/test'
import { createFamily } from '../support/factory'
import { login } from '../support/auth'

// 横長（3:1）の単色 SVG。画像ストレージを使わずにバナーの表示を確認する
const BANNER = `data:image/svg+xml,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="400"><rect width="1200" height="400" fill="#1e88e5"/><text x="600" y="220" font-size="80" text-anchor="middle" fill="white">BANNER</text></svg>',
)}`

test.describe('家族のバナー', () => {
    test('設定したバナーがホームのヒーローの背景になり、家族名などがその上に表示される', async ({ page, request }) => {
        const { owner, family } = await createFamily(request, { bannerUrl: BANNER, members: 1 })
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)

        const hero = page.locator('.home-hero')
        await expect(hero).toHaveClass(/home-hero--banner/)
        // 背景にバナー画像を使っている
        expect(await hero.evaluate((el) => getComputedStyle(el).backgroundImage)).toContain('data:image/svg+xml')
        // 家族名・メンバーのアイコンがバナーの中にある
        await expect(hero.getByText(family.name)).toBeVisible()
        await expect(hero.locator('.home-hero__avatar')).toHaveCount(2)
    })

    test('バナーが未設定ならヒーローはテーマカラーのまま', async ({ page, request }) => {
        const { owner } = await createFamily(request)
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)

        const hero = page.locator('.home-hero')
        await expect(hero).toBeVisible()
        await expect(hero).not.toHaveClass(/home-hero--banner/)
        expect(await hero.evaluate((el) => getComputedStyle(el).backgroundImage)).toContain('linear-gradient')
        expect(await hero.evaluate((el) => getComputedStyle(el).backgroundImage)).not.toContain('url(')
    })

    test('家族設定でバナーのプレビューと変更・削除のボタンが表示される（オーナー）', async ({ page, request }) => {
        const { owner } = await createFamily(request, { bannerUrl: BANNER })
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/family/settings')

        const card = page.locator('.v-card', { hasText: 'ホームのバナー' })
        await expect(card.locator('.image-preview--banner .v-img')).toBeVisible()
        await expect(card.getByRole('button', { name: '画像を選択', exact: true })).toBeVisible()
        await expect(card.getByRole('button', { name: '削除', exact: true })).toBeVisible()
        // 画像を選ぶまでは保存できない
        await expect(card.getByRole('button', { name: '保存' })).toBeDisabled()
    })
})

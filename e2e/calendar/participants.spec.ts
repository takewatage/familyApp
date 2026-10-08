import { expect, test } from '@playwright/test'
import { createFamily } from '../support/factory'
import { login } from '../support/auth'

// カレンダー（モックアップ）: 予定一覧の全画面表示・参加者アイコン・参加者の複数選択
test.describe('カレンダーの参加者', () => {
    test('予定に参加者を選んで追加すると、一覧の右端にアイコンが表示される', async ({ page, request }) => {
        // オーナー + メンバー 2 人の家族
        const { owner } = await createFamily(request, { members: 2 })
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/calendar')

        // ダミー予定にも参加者アイコンが表示される
        await expect(page.getByRole('img', { name: /^参加者:/ }).first()).toBeVisible()

        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('E2Eの予定')

        // 参加者をボトムシートで複数選択する
        await page.getByRole('button', { name: '参加者を選択' }).click()
        const sheet = page.locator('.participant-select-sheet')
        await expect(sheet.getByText('参加者を選択')).toBeVisible()
        await sheet.getByText('全員').click()
        await sheet.getByRole('button', { name: '決定（3人）' }).click()
        await expect(sheet).toBeHidden()

        await page.getByRole('button', { name: '保存' }).click()

        // 今日の予定として一覧に出て、右端に 3 人分のアイコンが出る
        const item = page.locator('.upcoming-event', { hasText: 'E2Eの予定' })
        await expect(item).toBeVisible()
        await expect(item.getByRole('img', { name: /^参加者:/ })).toBeVisible()
        await expect(item.locator('.participant-avatars__item')).toHaveCount(3)
    })

    test('日付をタップすると、その日の予定一覧が全画面で表示される', async ({ page, request }) => {
        const { owner } = await createFamily(request, { members: 1 })
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/calendar')

        await page.locator('.day-cell__num--today').filter({ visible: true }).first().click()

        const dialog = page.locator('.v-dialog--fullscreen')
        await expect(dialog).toBeVisible()
        await expect(dialog.getByText('買い出し')).toBeVisible()
        await expect(dialog.getByRole('img', { name: /^参加者:/ }).first()).toBeVisible()
    })

    test('タイトルをクリアして保存すると入力エラーが表示される', async ({ page, request }) => {
        const { owner } = await createFamily(request)
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/calendar')

        const errors: string[] = []
        page.on('pageerror', (e) => errors.push(e.message))

        // 既存の予定を編集で開き、× でタイトルを消して保存する
        await page.locator('.day-cell__num--today').filter({ visible: true }).first().click()
        await page.locator('.v-dialog--fullscreen').getByText('買い出し').click()
        await page.getByRole('button', { name: 'クリア タイトル' }).click()
        await page.getByRole('button', { name: '保存' }).click()

        await expect(page.getByText('タイトルを入力してください')).toBeVisible()
        expect(errors).toEqual([])
    })

    test('日別の予定一覧の右下の＋から、その日の予定を追加できる', async ({ page, request }) => {
        const { owner } = await createFamily(request)
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/calendar')

        await page.locator('.day-cell__num--today').filter({ visible: true }).first().click()
        await page.getByRole('button', { name: 'この日に予定を追加' }).click()

        await expect(page.getByText('予定を追加')).toBeVisible()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('一覧から追加')
        await page.getByRole('button', { name: '保存' }).click()

        await expect(page.locator('.upcoming-event', { hasText: '一覧から追加' })).toBeVisible()
    })

    test('予定が画面に収まらなくても、日別の予定一覧の＋ボタンは画面内に表示される', async ({ page, request }) => {
        const { owner } = await createFamily(request)
        await login(page, owner.email, owner.password)
        await expect(page).toHaveURL(/\/home$/)
        await page.goto('/calendar')

        // 予定 2 件でも一覧がはみ出す高さにする
        await page.setViewportSize({ width: 412, height: 260 })
        await page.locator('.day-cell__num--today').filter({ visible: true }).first().click()

        await expect(page.getByRole('button', { name: 'この日に予定を追加' })).toBeInViewport()
    })
})

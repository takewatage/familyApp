import { expect, test } from '@playwright/test'
import { createCalendarEvent } from '../support/factory'
import { openCalendar, openToday, waitForReload } from '../support/calendar'

// カレンダー: 参加者・日別の予定一覧（全画面・＋ボタン）・入力チェック
test.describe('カレンダーの参加者と予定一覧', () => {
    test('予定に参加者を選んで追加すると、一覧の右端にアイコンが表示される', async ({ page, request }) => {
        // オーナー + メンバー 2 人の家族
        await openCalendar(page, request, { members: 2 })

        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('E2Eの予定')

        // 参加者をボトムシートで複数選択する
        await page.getByRole('button', { name: '参加者を選択' }).click()
        const sheet = page.locator('.participant-select-sheet')
        await expect(sheet.getByText('参加者を選択')).toBeVisible()
        await sheet.getByText('全員').click()
        await sheet.getByRole('button', { name: '決定（3人）' }).click()
        await expect(sheet).toBeHidden()

        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())

        // 今日の予定として一覧に出て、右端に 3 人分のアイコンが出る
        const item = page.locator('.upcoming-event', { hasText: 'E2Eの予定' })
        await expect(item).toBeVisible()
        await expect(item.locator('.participant-avatars__item')).toHaveCount(3)
    })

    test('日付をタップすると、その日の予定一覧が全画面で表示される', async ({ page, request }) => {
        await openCalendar(page, request, { members: 1 }, async ({ family }) => {
            await createCalendarEvent(request, family.id, { title: '買い出し', startTime: '10:00', participants: true })
        })

        const dialog = await openToday(page)

        await expect(dialog.getByText('買い出し')).toBeVisible()
        await expect(dialog.getByText('10:00')).toBeVisible()
        await expect(dialog.getByRole('img', { name: /^参加者:/ }).first()).toBeVisible()
    })

    test('タイトルをクリアして保存すると入力エラーが表示される', async ({ page, request }) => {
        await openCalendar(page, request, {}, async ({ family }) => {
            await createCalendarEvent(request, family.id, { title: '買い出し' })
        })

        const errors: string[] = []
        page.on('pageerror', (e) => errors.push(e.message))

        // 既存の予定を編集で開き、× でタイトルを消して保存する
        const dialog = await openToday(page)
        await dialog.getByText('買い出し').click()
        await page.getByRole('button', { name: 'クリア タイトル' }).click()
        await page.getByRole('button', { name: '保存' }).click()

        await expect(page.getByText('タイトルを入力してください')).toBeVisible()
        expect(errors).toEqual([])
    })

    test('日別の予定一覧の右下の＋から、その日の予定を追加できる', async ({ page, request }) => {
        await openCalendar(page, request)

        await openToday(page)
        await page.getByRole('button', { name: 'この日に予定を追加' }).click()

        await expect(page.getByText('予定を追加')).toBeVisible()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('一覧から追加')
        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())

        await expect(page.locator('.upcoming-event', { hasText: '一覧から追加' })).toBeVisible()
    })

    test('予定が画面に収まらなくても、日別の予定一覧の＋ボタンは画面内に表示される', async ({ page, request }) => {
        await openCalendar(page, request, {}, async ({ family }) => {
            await createCalendarEvent(request, family.id, { title: '予定1' })
            await createCalendarEvent(request, family.id, { title: '予定2' })
        })

        // 予定 2 件でも一覧がはみ出す高さにする
        await page.setViewportSize({ width: 412, height: 260 })
        await openToday(page)

        await expect(page.getByRole('button', { name: 'この日に予定を追加' })).toBeInViewport()
    })
})

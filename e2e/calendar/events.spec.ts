import { expect, test } from '@playwright/test'
import { createCalendarEvent } from '../support/factory'
import { gotoCalendar, openCalendar, openToday, waitForReload } from '../support/calendar'

// カレンダー: 予定の保存・編集・削除、繰り返し予定の範囲指定、誕生日
test.describe('カレンダーの予定', () => {
    test('追加した予定は保存され、再読み込みしても表示される', async ({ page, request }) => {
        await openCalendar(page, request)

        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('保存される予定')
        await page.getByRole('textbox', { name: 'メモ' }).fill('持ち物: 水筒')
        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())

        await gotoCalendar(page)
        await expect(page.locator('.upcoming-event', { hasText: '保存される予定' })).toBeVisible()

        // 編集画面にメモも残っている
        const dialog = await openToday(page)
        await dialog.getByText('保存される予定').click()
        await expect(page.getByRole('textbox', { name: 'メモ' })).toHaveValue('持ち物: 水筒')
    })

    test('単発の予定を編集・削除できる', async ({ page, request }) => {
        await openCalendar(page, request, {}, async ({ family }) => {
            await createCalendarEvent(request, family.id, { title: '変更前' })
        })

        let dialog = await openToday(page)
        await dialog.getByText('変更前').click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('変更後')
        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())
        await expect(page.locator('.upcoming-event', { hasText: '変更後' })).toBeVisible()

        dialog = await openToday(page)
        await dialog.getByText('変更後').click()
        await page.getByRole('button', { name: 'この予定を削除' }).click()
        await expect(page.getByText('予定を削除しますか？')).toBeVisible()
        await waitForReload(page, () => page.getByRole('button', { name: '削除する' }).click())

        await expect(page.getByText('これから7日間の予定はありません')).toBeVisible()
    })

    test('繰り返し予定を作成し、この回だけ変更・これ以降を削除できる', async ({ page, request }) => {
        await openCalendar(page, request)

        // 毎日・3 回の予定を作成
        await page.getByRole('button', { name: '予定を追加' }).click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('朝の体操')
        await page.getByRole('button', { name: '繰り返しを設定' }).click()
        await page.getByRole('radio', { name: '毎日' }).or(page.locator('.v-chip', { hasText: '毎日' })).first().click()
        await page.getByLabel('回数を指定').check()
        await page.getByRole('spinbutton', { name: '回数' }).fill('3')
        await page.getByRole('button', { name: '決定' }).click()
        await expect(page.getByRole('button', { name: '繰り返しを設定' }).locator('input')).toHaveValue(new RegExp('毎日（3回）'))
        await waitForReload(page, () => page.getByRole('button', { name: '保存' }).click())

        const items = page.locator('.upcoming-event', { hasText: '朝の体操' })
        await expect(items).toHaveCount(3)

        // 今日の回だけタイトルを変更
        let dialog = await openToday(page)
        await dialog.getByText('朝の体操').click()
        await page.getByRole('textbox', { name: /タイトル/ }).fill('今日だけ散歩')
        await page.getByRole('button', { name: '保存' }).click()
        await expect(page.getByText('繰り返しの予定を変更')).toBeVisible()
        await waitForReload(page, () => page.getByText('この予定のみ').click())

        await expect(page.locator('.upcoming-event', { hasText: '今日だけ散歩' })).toHaveCount(1)
        await expect(items).toHaveCount(2)

        // 明日の回以降を削除 → 今日の回（変更済み）だけ残る
        // カレンダー上の「朝の体操」の最初のチップ（＝明日の回）をタップしてその日の一覧を開く。
        // 明日が翌月のときは当月に表示されないため、翌月へ移動する
        const chip = page.locator('.month-grid').nth(1).locator('.day-cell__event', { hasText: '朝の体操' })
        if ((await chip.count()) === 0) {
            await page.locator('button:has(.mdi-chevron-right)').filter({ visible: true }).first().click()
        }
        await chip.first().click()
        dialog = page.locator('.v-dialog--fullscreen')
        await dialog.getByText('朝の体操').click()
        await page.getByRole('button', { name: 'この予定を削除' }).click()
        await expect(page.getByText('繰り返しの予定を削除')).toBeVisible()
        await waitForReload(page, () => page.getByText('これ以降の予定').click())

        await expect(items).toHaveCount(0)
        await expect(page.locator('.upcoming-event', { hasText: '今日だけ散歩' })).toHaveCount(1)
    })

    test('家族の誕生日が表示され、タップしても編集画面は開かない', async ({ page, request }) => {
        const { owner } = await openCalendar(page, request, { ownerBirthdayToday: true })

        await expect(page.locator('.upcoming-event', { hasText: `${owner.name}の誕生日` })).toBeVisible()

        const dialog = await openToday(page)
        await dialog.getByText(`${owner.name}の誕生日`).click()

        await expect(page.getByText('予定を編集')).toHaveCount(0)
    })

    test('予定の編集画面が画面に収まらなくても、スクロールして下のボタンまで操作できる', async ({ page, request }) => {
        await openCalendar(page, request, {}, async ({ family }) => {
            await createCalendarEvent(request, family.id, { title: '長いフォーム' })
        })

        await page.setViewportSize({ width: 412, height: 480 })
        const dialog = await openToday(page)
        await dialog.getByText('長いフォーム').click()

        const remove = page.getByRole('button', { name: 'この予定を削除' })
        await expect(remove).not.toBeInViewport()

        // 指やホイールでのスクロールと同じく、フォームの上でホイールを回す
        // （scrollIntoView はプログラムからのスクロールで overflow: hidden でも動いてしまうため使わない）
        await page.getByRole('textbox', { name: /タイトル/ }).hover()
        await page.mouse.wheel(0, 2000)

        await expect(remove).toBeInViewport()
    })
})

import { expect, type Page } from '@playwright/test'

export async function login(
    page: Page,
    email: string,
    password: string,
): Promise<void> {
    await page.goto('/login')
    await page.getByLabel('アカウント').fill(email)
    // 目のアイコン（表示切替ボタン）も同じラベルを持つため textbox に限定する
    await page.getByRole('textbox', { name: /パスワードを入力/ }).fill(password)
    await page.getByRole('button', { name: 'ログイン' }).click()
}

export async function fillRegisterForm(
    page: Page,
    user: { name: string; email: string; password: string },
): Promise<void> {
    await page.getByLabel('名前').fill(user.name)
    await page.getByLabel('メールアドレス').fill(user.email)
    await page.getByLabel('パスワード', { exact: true }).fill(user.password)
    await page.getByLabel('パスワード（確認）').fill(user.password)
}

export async function expectOnHome(
    page: Page,
    familyName: string,
): Promise<void> {
    await expect(page).toHaveURL(/\/home$/)
    await expect(page.getByText(familyName).first()).toBeVisible()
}

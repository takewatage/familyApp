import { execSync } from 'node:child_process'
import { E2E_ENV } from '../playwright.config'

/**
 * テスト開始前に E2E 専用 DB を作り直す
 *
 * `migrate --force` は DB が無ければ作成する（sail ユーザーは testing% の DB を作成できる）。
 */
export default function globalSetup(): void {
    const env = { ...process.env, ...E2E_ENV }

    execSync('php artisan migrate --force', { env, stdio: 'inherit' })
    execSync('php artisan migrate:fresh --force', { env, stdio: 'inherit' })
}

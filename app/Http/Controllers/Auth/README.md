# 認証（Auth）

このディレクトリは認証まわりのコントローラーを配置している。本 README は **認証方式の仕様と、動かすために必要な設定手順** をまとめたもの。

## 認証方式

| 方式 | 入口 | 備考 |
|---|---|---|
| メールアドレス + パスワード | `/login` | 家族コードの入力は不要 |
| Google アカウント | `/login`・`/register` の「Googleでログイン」 | `GOOGLE_CLIENT_ID` 未設定時はボタン非表示 |

新規登録（`/register`）は招待 URL がなくても可能。招待セッションがあれば招待先の家族へ参加し、なければ「{ユーザー名}の家族」を自動作成する。

> 家族コード（`families.code`）は招待機能（`/join/{code}`）専用に戻っており、**ログインの認証条件ではない**。

## 関連ファイル

```
app/Http/Controllers/Auth/
├── AuthenticatedSessionController.php   ログイン画面表示 / ログイン / ログアウト
├── RegisteredUserController.php         新規登録画面表示 / 登録
├── GoogleAuthController.php             Google認可リダイレクト / コールバック
└── （その他: パスワードリセット・メール確認・パスワード変更）

app/Http/Requests/Auth/LoginRequest.php  メール/パスワード認証・レート制限
app/Services/SocialAuthService.php       Googleユーザーの特定 / 作成
app/Services/FamilyProvisionService.php  新規ユーザーの家族割り当て（招待参加 or 家族作成）
app/Services/CurrentFamilyService.php    現在の家族の決定・セッション保存
app/Exceptions/SocialAuthException.php   Google連携時の業務エラー
app/Dtos/Auth/LoginPageResult.php        ログイン画面 DTO（googleEnabled 等）
app/Dtos/Auth/RegisterPageResult.php     登録画面 DTO（familyName / googleEnabled）

resources/js/Pages/Auth/Login.vue
resources/js/Pages/Auth/Register.vue
resources/js/Components/Auth/SocialLoginButtons.vue

database/migrations/2026_08_01_000001_add_google_id_to_users_table.php
database/migrations/2026_08_01_000002_add_last_family_id_to_users_table.php

tests/Feature/Auth/AuthenticationTest.php
tests/Feature/Auth/RegistrationTest.php
tests/Feature/Auth/GoogleAuthTest.php
```

## ルート

| メソッド | パス | ルート名 | 内容 |
|---|---|---|---|
| GET | `/login` | `login` | ログイン画面 |
| POST | `/login` | — | ログイン |
| GET | `/register` | `register` | 新規登録画面 |
| POST | `/register` | — | 新規登録 |
| GET | `/auth/google/redirect` | `auth.google.redirect` | Google 認可画面へリダイレクト |
| GET | `/auth/google/callback` | `auth.google.callback` | Google からのコールバック |
| POST | `/logout` | `logout` | ログアウト |

Google の 2 ルートは `guest` ミドルウェアグループ内にあるため、**ログイン済みの状態でアクセスするとホームへリダイレクトされる**。

---

## セットアップ手順

### 1. マイグレーション

`users` テーブルに `google_id` / `last_family_id` を追加し、`password` を nullable 化する。

```bash
sail artisan migrate
```

| カラム | 型 | 用途 |
|---|---|---|
| `google_id` | `string` nullable unique | Google アカウント ID（OpenID Connect の `sub`） |
| `last_family_id` | `uuid` nullable（`families` へ FK / `nullOnDelete`） | 最後に選択していた家族 |
| `password` | `string` **nullable に変更** | Google のみで登録したユーザーはパスワードを持たない |

### 2. Google OAuth クライアントの発行

Google ログインを使う場合のみ必要。**未設定でもアプリは動作する**（Google ボタンが非表示になるだけ）。

1. [Google Cloud Console](https://console.cloud.google.com/) でプロジェクトを作成（または既存プロジェクトを選択）
2. **Google Auth Platform → Clients**（<https://console.cloud.google.com/auth/clients>）を開く
   - 初回はブランディング（アプリ名・サポートメール）と対象ユーザー（外部）の設定を求められる
3. **Create client（クライアントを作成）** をクリック
4. アプリケーションの種類に **ウェブ アプリケーション（Web application）** を選択
5. **承認済みのリダイレクト URI** に、アプリの `APP_URL` + `/auth/google/callback` を登録する
   - ローカル: `http://localhost/auth/google/callback`
   - ポート指定でアクセスしている場合はそのポートを含める（例: `http://localhost:8080/auth/google/callback`）
   - 本番: `https://<本番ドメイン>/auth/google/callback`
   - リダイレクト URI は**完全一致**で照合される。`localhost` に限り HTTP スキームが許可される
6. 発行された **クライアント ID / クライアント シークレット** を控える
7. テスト段階（公開ステータスが「テスト」）の場合は、ログインに使う Google アカウントを **テストユーザー** に追加する

> リクエストするスコープは Socialite の Google ドライバ既定値（`openid` / `profile` / `email`）。追加設定は不要。

### 3. `.env` の設定

```dotenv
APP_URL=http://localhost

GOOGLE_CLIENT_ID=発行されたクライアントID
GOOGLE_CLIENT_SECRET=発行されたクライアントシークレット
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

`config/services.php` の `google` キーから参照される。`GOOGLE_REDIRECT_URI` は Google Cloud Console に登録した URI と**完全一致**させること。

設定後、キャッシュを使っている環境では反映のためにクリアする。

```bash
sail artisan config:clear
```

### 4. 反映確認

- `/login` に「Googleでログイン」ボタンが表示されれば `GOOGLE_CLIENT_ID` が読めている
  （ボタンの表示可否は `filled(config('services.google.client_id'))` を `googleEnabled` として画面に渡している）
- ボタンから Google の同意画面まで遷移できれば、リダイレクト URI の登録も一致している

---

## 動作仕様

### Google ログインのユーザー特定順序

`SocialAuthService::findOrCreateGoogleUser()`:

1. `google_id` が一致するユーザーがいる → そのユーザーでログイン
2. Google 側でメール未確認（`email_verified` が false）→ **エラー**（既存アカウントへの自動紐付けを許可しない）
3. 同じメールアドレスのユーザーがいる → `google_id` を紐付けて連携（`email_verified_at` も未設定なら埋める）
4. いずれも該当なし → 新規作成（`password` は `null`、`email_verified_at` は確認済み）

同意画面でキャンセルされた場合（`error` パラメータ付きで戻る）、および Google からのユーザー情報取得に失敗した場合は、ログイン画面にエラーメッセージを表示して戻す。

### パスワード未設定ユーザーの扱い

Google のみで登録したユーザーは `password` が `null`。`LoginRequest::authenticate()` はこの状態のメールアドレスでのパスワード認証を拒否し、「Googleログインからログインしてください」というエラーを返す（レート制限のカウント対象）。

### 新規ユーザーの家族割り当て

`FamilyProvisionService::provisionForNewUser()`（メール登録・Google 登録の両方から呼ばれる）:

- 招待セッション（`invite_family_code`）が有効 → その家族へ参加（ロールは `invite_role`、不正値なら `guest`）
- 招待なし → 「{ユーザー名}の家族」を作成し、本人を `owner` として所属させる
- いずれの場合も、割り当てた家族を「現在の家族」としてセッションに保存する

### 現在の家族の決定

`CurrentFamilyService::resolveAndSetForUser()`（ログイン直後に呼ばれる）:

1. `users.last_family_id` の家族（現在も所属している場合のみ）
2. なければ参加日時が最も古い所属家族
3. 所属家族がなければセッションをクリア

家族を切り替えると `setCurrentFamily()` が `last_family_id` を更新するため、次回ログイン時に同じ家族が選ばれる。

---

## テスト

```bash
sail artisan test --filter=Auth
```

Google 連携のテストは `Socialite::fake()` を使うため、**実際の OAuth クライアントは不要**（`tests/Feature/Auth/GoogleAuthTest.php`）。

```php
// tests/Feature/Auth/GoogleAuthTest.php
Socialite::fake('google', SocialiteUser::fake([
    'id' => 'google-123',
    'name' => 'グーグル太郎',
    'email' => 'google-taro@example.com',
    'email_verified' => true,
]));
```

## トラブルシューティング

| 症状 | 原因と対処 |
|---|---|
| Google ボタンが表示されない | `GOOGLE_CLIENT_ID` が未設定、または `config:clear` していない |
| `redirect_uri_mismatch` | Google Cloud Console の承認済みリダイレクト URI と `GOOGLE_REDIRECT_URI` が不一致。ポート・スキーム・末尾スラッシュまで一致させる |
| `access_blocked` / 「アプリは確認されていません」 | 公開ステータスが「テスト」でテストユーザー未登録。Google Auth Platform でテストユーザーに追加する |
| 「メールアドレスが確認済みではないため…」 | Google 側でメール未確認のアカウント。既存アカウントへの自動紐付けは仕様上許可していない |
| Google ログイン後に別ユーザーになる | 同じメールアドレスの既存ユーザーへ紐付いた可能性がある（仕様どおりの挙動） |
| ログイン後に家族が選択されない | ユーザーがどの家族にも所属していない。招待 URL 経由で参加するか、家族を作成する |

## 補足

- `User` は `MustVerifyEmail` を実装していないため、**登録時のメール確認は現状必須になっていない**。`routes/auth.php` のメール確認ルート（Breeze 由来）は残っているが、`verified` ミドルウェアはどのルートにも適用していない。必須化する場合は `User` に `MustVerifyEmail` を実装し、対象ルートに `verified` を付与する
- 使用パッケージ: `laravel/socialite`（`composer.json` 参照）
- 要件・設計の詳細: `docs/working/20260801_auth-email-google/`

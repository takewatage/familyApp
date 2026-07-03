# 技術仕様書

## 1. 技術スタック

### 言語・フレームワーク

| カテゴリ         | 技術             | バージョン | 用途                                     |
|----------------|-----------------|----------|----------------------------------------|
| バックエンド      | Laravel         | 12.x     | Webアプリケーションフレームワーク             |
| バックエンド言語  | PHP             | 8.2+     | サーバーサイド処理                          |
| フロントエンド    | Vue.js          | 3.x      | UIコンポーネントフレームワーク                |
| フロントエンド言語| TypeScript      | 5.6      | 型安全なJavaScript                       |
| UIフレームワーク  | Vuetify         | 3.10.5   | Materialデザインコンポーネントライブラリ       |
| ブリッジ         | Inertia.js      | 2.x      | LaravelとVueをSPAとして繋ぐブリッジ          |
| ビルドツール      | Vite            | 6.0      | 高速なフロントエンドビルドツール              |

### 主要ライブラリ

| ライブラリ                          | バージョン | 用途                                         |
|------------------------------------|----------|---------------------------------------------|
| Spatie Laravel Data                | -        | 型安全なDTO（Data Transfer Object）           |
| Spatie Laravel TypeScript Transformer | -     | PHPのDTO定義からTypeScript型を自動生成         |
| Intervention Image Laravel         | 1.5      | 画像処理（リサイズ・WebP変換）                 |
| Laravel Sanctum                    | 4.0      | APIトークン認証                               |
| Pusher / Laravel Broadcasting      | -        | WebSocketリアルタイム通信                      |
| Ziggy                              | -        | JavaScriptでLaravelルートを使用               |
| Axios                              | 1.11     | HTTPクライアント                               |
| VueUse                             | 14.0     | Vue3 Composition API ユーティリティ           |
| Day.js                             | 1.11     | 日付処理                                     |
| vue-draggable-plus                 | 0.6      | ドラッグ＆ドロップによる並び替え               |
| Laravel Echo                       | -        | WebSocketイベントのフロントエンド受信          |
| qrcode                             | -        | QRコード生成（DataURL → `<img>` 表示）       |

## 2. アーキテクチャ概要

### コンポーネント構成

```
[ブラウザ]
  └─ Vue 3 + Vuetify (SPA ライク)
       └─ Inertia.js
            └─ [Laravel 12 サーバー]
                  ├─ Controller（HTTP処理）
                  ├─ Service（ビジネスロジック）
                  ├─ Model（Eloquent ORM）
                  ├─ DTO（Spatie Laravel Data）
                  └─ Database（SQLite/PostgreSQL）

[WebSocket]
  ブラウザ（Laravel Echo） ←→ Pusher ←→ Laravel Broadcasting
```

### データフロー

1. **画面遷移（Inertia）**: ブラウザから `Inertia::render()` でVueコンポーネントとPropsを受け取り描画
2. **フォーム送信（Inertia Form）**: `useInertiaForm` でHTTPリクエストを送信、レスポンスでページを再レンダリング
3. **API通信（Axios）**: `client.ts` 経由でAxiosリクエスト送信（camelCase ↔ snake_case 自動変換）
4. **リアルタイム更新**: Pusher Private Channel `family.{family_id}` で `TaskUpdated`・`CategoryUpdated` イベントを配信
5. **型安全**: PHPの `#[TypeScript]` DTO → `dto.generated.d.ts` に自動生成 → フロントエンドで使用
6. **共有プロパティ**: `HandleInertiaRequests::share()` で全ページに `userSettings`（theme, themeName, footerItems）を配信 → `useAppTheme` composable が Vuetify のプライマリー・セカンダリー両テーマカラーへ即時反映。`footerItems` は `AuthenticatedLayout.vue` がフッターナビゲーションの表示に使用
7. **署名付き招待URL**: `URL::signedRoute()` でサーバーサイドが署名を生成。`InviteController` で `hasValidSignature()` を検証し、クライアントによる `role` パラメータの改ざんを防止

## 3. 開発環境

### 前提条件

- Docker（Laravel Sail で起動）
- Composer（初回の `sail` コマンド実行前に必要）

### セットアップ手順

```bash
# 初回セットアップ
composer install
cp .env.example .env
sail up -d
sail artisan key:generate
sail artisan migrate
sail yarn install
sail yarn build
```

### 開発コマンド

| コマンド                             | 説明                                  |
|------------------------------------|--------------------------------------|
| `sail up -d`                       | Dockerコンテナをバックグラウンドで起動    |
| `sail down`                        | Dockerコンテナを停止                   |
| `sail yarn dev`                    | Vite 開発サーバー起動                  |
| `sail yarn build`                  | 本番用フロントエンドビルド               |
| `sail artisan migrate`             | DBマイグレーション実行                  |
| `sail artisan test`                | PHPUnit テスト実行                     |
| `sail artisan typescript:transform`| DTO → TypeScript型定義ファイル自動生成  |
| `sail artisan pint`                | PHP コードフォーマット（Laravel Pint）  |
| `sail yarn lint`                   | 変更ファイルのみ ESLint 実行            |
| `sail yarn fix`                    | フォーマット + Lint 一括実行            |

## 4. API一覧

### Web Routes（Inertia）

| エンドポイント                        | メソッド | 説明                          | 状態    |
|-------------------------------------|---------|------------------------------|---------|
| `/join/{code}`                      | GET     | 招待確認ページ（guest/auth共通） | ✅完了  |
| `/join/{code}`                      | POST    | ログイン済みユーザーの家族参加    | ✅完了  |
| `/home`                             | GET     | ホーム画面（`HomeResult`: メンバー・仮想ユーザーをアバター込みで返却） | ✅完了  |
| `/mypage`                           | GET     | マイページ画面                  | ✅完了  |
| `/mypage`                           | POST    | プロフィール更新（`name`, `birthday`, `avatar_image`, `delete_avatar`）| ✅完了  |
| `/mypage/settings`                  | GET     | 設定ページ                      | ✅完了  |
| `/mypage/settings`                  | POST    | ユーザー設定保存（テーマ・テーマカラー） | ✅完了  |
| `/mypage/footer-settings`           | GET     | アプリショートカット設定ページ    | ✅完了  |
| `/mypage/footer-settings`           | POST    | フッター項目の保存               | ✅完了  |
| `/tasks`                            | GET     | タスク一覧画面                  | ✅完了  |
| `/task`                             | POST    | タスク作成                     | ✅完了  |
| `/task/{task}`                      | PATCH   | タスク更新                     | ✅完了  |
| `/task/{task_id}/toggle`            | PATCH   | タスク完了トグル                | ✅完了  |
| `/task/{task}`                      | DELETE  | タスク削除                     | ✅完了  |
| `/tasks/completed`                  | DELETE  | 完了タスク一括削除（7日以内）    | ✅完了  |
| `/task-categories`                  | POST    | カテゴリー作成                  | ✅完了  |
| `/task-categories/{taskCategory}`   | PATCH   | カテゴリー更新                  | ✅完了  |
| `/task-categories/{taskCategory}`   | DELETE  | カテゴリー削除                  | ✅完了  |
| `/task-categories/reorder`          | POST    | カテゴリー並び替え              | ✅完了  |
| `/dok`                              | GET     | Dok画面                       | ⚠️一部完了 |
| `/family/settings`                  | GET     | 家族設定変更ページ              | ✅完了     |
| `/family/settings`                  | PATCH   | 家族基本情報の更新（オーナーのみ）| ✅完了     |
| `/family/code/regenerate`           | POST    | 家族コード再生成（オーナーのみ）  | ✅完了     |
| `/family/members`                   | GET     | メンバー管理ページ              | ✅完了     |
| `/family/members/{user}`            | DELETE  | メンバー除名（オーナーのみ）     | ✅完了     |
| `/family/virtual-users`             | POST    | 仮想ユーザー作成               | ✅完了     |
| `/family/virtual-users/{virtualUser}` | PATCH | 仮想ユーザー更新               | ✅完了     |
| `/family/virtual-users/{virtualUser}` | DELETE| 仮想ユーザー削除               | ✅完了     |
| `/families/switch`                  | GET     | 家族切り替えページ              | ✅完了     |
| `/families/{family}/switch`         | POST    | アクティブ家族の切り替え         | ✅完了     |
| `/budget/expenses`                  | GET     | 支出一覧画面（月指定 `?month=YYYY-MM`。`ExpensePageResult`: 支出・カテゴリー/支払い方法/店舗/担当者の選択肢・月合計） | ✅完了 |
| `/budget/expenses`                  | POST    | 支出登録（`StoreExpenseRequest`。FK は family スコープ検証、店舗は既存名紐付け or テキスト保持） | ✅完了 |
| `/budget/expenses/{expense}`        | PATCH   | 支出更新（`UpdateExpenseRequest`。family 越境は404、FK は family スコープ検証） | ✅完了 |
| `/budget/expenses/{expense}`        | DELETE  | 支出削除（family 越境は404、店舗の利用回数を減算） | ✅完了 |
| `/budget/categories`                | GET     | カテゴリー管理画面（`CategoriesPageResult`: 家族＋システム既定カテゴリー） | ✅完了 |
| `/budget/categories`                | POST    | カテゴリー作成（`StoreCategoryRequest`。親は family/システム既定の最上位のみ＝親子2階層制限） | ✅完了 |
| `/budget/categories/{category}`     | PATCH   | カテゴリー更新（`UpdateCategoryRequest`。family 越境/システム既定は404、自己親・子持ち親化を防止） | ✅完了 |
| `/budget/categories/{category}`     | DELETE  | カテゴリー論理削除（`is_active=false`、子も無効化。family 越境/システム既定は404） | ✅完了 |
| `/budget/shops`                     | GET     | 店舗管理画面（`ShopsPageResult`: 家族の店舗・カテゴリー選択肢） | ✅完了 |
| `/budget/shops`                     | POST    | 店舗作成（`StoreShopRequest`。family 内で店舗名一意・既定カテゴリーは family スコープ） | ✅完了 |
| `/budget/shops/{shop}`              | PATCH   | 店舗更新（`UpdateShopRequest`。family 越境は404・店舗名一意は自分を除外） | ✅完了 |
| `/budget/shops/{shop}`              | DELETE  | 店舗削除（family 越境は404。支出の `shop_id` は nullOnDelete） | ✅完了 |
| `/budget/payment-methods`           | GET     | 支払い方法管理画面（`PaymentMethodsPageResult`: 家族＋システム既定の有効な支払い方法） | ✅完了 |
| `/budget/payment-methods`           | POST    | 支払い方法作成（`StorePaymentMethodRequest`。名前・アイコン） | ✅完了 |
| `/budget/payment-methods/{paymentMethod}` | PATCH | 支払い方法更新（family 越境/システム既定は404） | ✅完了 |
| `/budget/payment-methods/{paymentMethod}` | DELETE | 支払い方法論理削除（`is_active=false`。family 越境/システム既定は404） | ✅完了 |
| `/budget/quick-entries`             | GET     | クイック入力管理画面（`QuickEntriesPageResult`: クイック入力・カテゴリー/支払い方法/店舗の選択肢。利用頻度降順） | ✅完了 |
| `/budget/quick-entries`             | POST    | クイック入力作成（`StoreQuickEntryRequest`。FK は family/システム既定スコープ検証、店舗は family スコープ） | ✅完了 |
| `/budget/quick-entries/{quickEntry}` | PATCH  | クイック入力更新（family 越境は404） | ✅完了 |
| `/budget/quick-entries/{quickEntry}` | DELETE | クイック入力削除（物理削除。family 越境は404） | ✅完了 |
| `/budget/recurring-expenses`        | GET     | 繰り返し支出（固定費）管理画面（`RecurringExpensesPageResult`: 有効な繰り返し支出・カテゴリー/支払い方法/店舗/担当者の選択肢） | ✅完了 |
| `/budget/recurring-expenses`        | POST    | 繰り返し支出作成（`StoreRecurringExpenseRequest`。FK は family/システム既定スコープ検証、担当者は family スコープ、支払日1-31） | ✅完了 |
| `/budget/recurring-expenses/{recurringExpense}` | PATCH | 繰り返し支出更新（family 越境は404、FK は family スコープ検証） | ✅完了 |
| `/budget/recurring-expenses/{recurringExpense}` | DELETE | 繰り返し支出の無効化（`is_active=false`。物理削除せず生成済み支出の参照を保つ。family 越境は404） | ✅完了 |

### コンソールコマンド（スケジュール）

| コマンド | スケジュール | 説明 | 状態 |
|---------|------------|------|------|
| `budget:generate-recurring-expenses` | 日次 01:00（`routes/console.php`） | 支払日が到来した繰り返し支出から `expenses` を生成（`is_recurring=true`・`recurring_expense_id` 紐付け）。冪等性は「当月に同一 recurring から生成済みの支出が存在するか」の存在チェックで担保（`last_generated_date` は fast-path の目安）。生成・店舗 usage_count 加算・marker 更新は `DB::transaction` で原子的。`--date=Y-m-d` で任意月を安全に補完可能（未実行月の自動キャッチアップは非対応・手動補完前提） | ✅完了 |

### API Routes（Axios）

| エンドポイント | メソッド | 説明 | 状態 |
|-------------|---------|------|------|
| `/budget/categories/reorder` | POST | カテゴリー並び替え（`ReorderCategoriesRequest`。家族カテゴリーのみ、`budgetCategoryApi.reorder`） | ✅完了 |
| `/budget/shops/search` | GET | 店名オートコンプリート（家族スコープ・利用回数降順、`budgetShopApi.search`） | ✅完了 |
| `/budget/quick-entries/{quickEntry}/use` | POST | クイック入力の利用回数加算（支出フォームへのプリセット時、`budgetQuickEntryApi.use`。family 越境は404） | ✅完了 |

## 5. データベース

### テーブル一覧

| テーブル名             | 説明                                    |
|----------------------|-----------------------------------------|
| `users`              | ユーザー基本情報（UUID, name, email, birthday nullable, settings JSON nullable）settings スキーマ: `{ theme: 'light'\|'dark'\|'system', theme_name: 'pink'\|'sunset'\|'ocean'\|'forest'\|'lavender'\|'autumn'\|'midnight', footer_items: string[] }` |
| `families`           | 家族グループ情報（UUID, name, code, code_expires_at nullable, owner_id）|
| `family_user`        | ユーザーと家族グループの中間テーブル（role付き）|
| `task_categories`    | タスクカテゴリー（family_id, name, sort）  |
| `tasks`              | タスク（family_id, category_id, content, color, memo, is_completed, sort）|
| `files`              | ファイル管理（ポリモーフィック: avatar等）  |
| `virtual_users`      | 仮想ユーザー（family_id, name）アバターはfilesテーブルで管理 |
| `password_reset_tokens` | パスワードリセット用トークン             |
| `sessions`           | ユーザーセッション                        |
| `cache`              | キャッシュテーブル                        |
| `jobs`               | ジョブキュー                             |

### 主要なリレーション

- `User` ↔ `Family`: 多対多（`family_user` 中間テーブル、role属性）
- `Family` → `VirtualUser`: 1対多
- `VirtualUser` → `File`: 1対多（ポリモーフィック、avatar）
- `Family` → `TaskCategory`: 1対多
- `TaskCategory` → `Task`: 1対多
- `User` → `File`: 1対多（ポリモーフィック）

## 6. 外部連携

| 連携先  | 方式                    | 用途                                         |
|--------|------------------------|---------------------------------------------|
| Pusher | WebSocket（Laravel Broadcasting）| タスク・カテゴリーのリアルタイム同期          |
| HStorage | HTTP API（presigned URL + PUT / DELETE）| 画像ファイルのクラウドストレージ保存・削除 |

### HStorage ファイル命名規則

HStorage はフォルダ管理非対応のフラットストレージ。ファイル名にパスライクな文字列を含めて管理する。

```
familyApp/{family_uuid}/{collection}/{ULID}.webp
```

| セグメント | 値の例 | 説明 |
|---|---|---|
| `familyApp` | 固定プレフィックス | アプリ識別子 |
| `{family_uuid}` | `01JXXXXXXXXXXXXXXXXXXXXXXX` | 所属家族の UUID |
| `{collection}` | `avatar` | ファイルの用途（filesテーブルの collection と対応） |
| `{ULID}.webp` | `01JXXXXXXXXXXXXXXXXXXXXXXX.webp` | ULID + WebP固定 |

- `family_uuid` が確定していない場合（登録フロー等）は `unassigned` を使用
- `ImageUploadService::upload()` の `storagePath` 引数でパスを指定する
- ファイルの論理的な管理は `files` テーブル（`fileable_type`, `fileable_id`, `collection`）で行い、HStorage はキーバリューストアとして割り切る

### HStorage 削除 API

```
DELETE https://api.hstorage.io/file/my?external_id={external_id}
```

- `external_id` はクエリパラメータで渡す
- 削除成功時にサーバーが空レスポンス（cURL error 52: Empty reply）を返す仕様のため、`ConnectionException` をキャッチして正常完了とみなす
- `files` テーブルの `path` カラムに HStorage の `external_id` を保存しており、削除時は `$file->path` を使用する

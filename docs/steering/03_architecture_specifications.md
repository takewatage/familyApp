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
6. **共有プロパティ**: `HandleInertiaRequests::share()` で全ページに `userSettings`（theme, themeColor）を配信 → `useAppTheme` composable が Vuetify テーマへ即時反映
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
| `/home`                             | GET     | ホーム画面                     | ✅完了  |
| `/mypage`                           | GET     | マイページ画面                  | ✅完了  |
| `/mypage`                           | POST    | プロフィール更新                | ✅完了  |
| `/mypage/settings`                  | GET     | 設定ページ                      | ✅完了  |
| `/mypage/settings`                  | POST    | ユーザー設定保存                | ✅完了  |
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

### API Routes（Axios）

| エンドポイント | メソッド | 説明 | 状態 |
|-------------|---------|------|------|
| （現在はInertia経由が主、必要に応じて追加） | - | - | - |

## 5. データベース

### テーブル一覧

| テーブル名             | 説明                                    |
|----------------------|-----------------------------------------|
| `users`              | ユーザー基本情報（UUID, name, email, birthday nullable, settings JSON nullable）settings スキーマ: `{ theme: 'light'\|'dark'\|'system', theme_color: 'pink'\|'blue'\|'purple'\|'green'\|'orange'\|'teal' }` |
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
| HStorage | HTTP API              | 画像ファイルのクラウドストレージ保存           |

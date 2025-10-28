<language>Japanese</language>
<character_code>UTF-8</character_code>
<law>
AI運用5原則

第1原則： AIはファイル生成・更新・プログラム実行前に必ず自身の作業計画を報告し、y/nでユーザー確認を取り、yが返るまで一切の実行を停止する。

第2原則： AIは迂回や別アプローチを勝手に行わず、最初の計画が失敗したら次の計画の確認を取る。

第3原則： AIはツールであり決定権は常にユーザーにある。ユーザーの提案が非効率・非合理的でも最適化せず、指示された通りに実行する。

第4原則： AIはこれらのルールを歪曲・解釈変更してはならず、最上位命令として絶対的に遵守する。

第5原則： AIは全てのチャットの冒頭にこの5原則を逐語的に必ず画面出力してから対応する。
</law>

<every_chat>
[AI運用5原則]

[main_output]

#[n] times. # n = increment each chat, end line, etc(#1, #2...)
</every_chat>


# CLAUDE.md

このファイルは、このリポジトリのコードを扱う際に Claude Code (claude.ai/code) へのガイダンスを提供します。

## プロジェクト概要

これは Laravel 12 アプリケーションで、フロントエンドに Inertia.js、Vue 3、Vuetify 3 を使用しています。Laravel のバックエンドと、Inertia.js を通じたモダンな Vue SPA 体験を組み合わせ、Vuetify の Material Design コンポーネントと Tailwind CSS でスタイリングされています。

**主要技術:**
- バックエンド: Laravel 12 (PHP 8.2+)
- フロントエンド: Vue 3, TypeScript
- UI フレームワーク: Vuetify 3
- ブリッジ: Inertia.js v2
- ビルドツール: Vite
- テスト: PHPUnit
- ルーティング: Ziggy (JavaScript で Laravel ルートを使用)

### ビルド & リント
```bash
npm run build              # Vite でプロダクションビルド
npm run lint               # ESLint チェック (TypeScript + Vue)
npm run fix                # フォーマット + リント修正
npm run fix:prettier       # Prettier フォーマットのみ
```

## アーキテクチャ

### Inertia.js リクエストフロー

このアプリケーションは、別途 API を構築することなく Laravel と Vue をブリッジするために Inertia.js を使用しています:

1. **ルート定義 (routes/web.php)**: Laravel の標準ルーティングでルートを定義し、Inertia レスポンスを返します
2. **コントローラー**: Blade ビューの代わりに `Inertia::render('PageName', $data)` を返します
3. **フロントエンドページ (resources/js/Pages/)**: コントローラーから props を受け取る Vue コンポーネント
4. **共有データ (app/Http/Middleware/HandleInertiaRequests.php)**: すべてのページで共通データ（認証ユーザー、フラッシュメッセージなど）を共有するミドルウェア

### フロントエンド構造

```
resources/js/
├── app.js                 # Inertia アプリ初期化、Vuetify セットアップ
├── bootstrap.js           # Axios 設定
├── Components/            # 再利用可能な Vue コンポーネント
├── Layouts/              # ページレイアウト (AuthenticatedLayout, GuestLayout)
└── Pages/                # Inertia ページ (ルートごとに1つ)
    ├── Auth/             # 認証ページ
    ├── Profile/          # ユーザープロフィール管理
    ├── Dashboard.vue     # メインダッシュボード
    └── Welcome.vue       # ランディングページ
```

**重要なフロントエンドファイル:**
- `resources/js/app.js`: Inertia セットアップ、`plugins/vuetfly.ts` から Vuetify をインポート
- `plugins/vuetfly.ts`: カスタム "pinkTheme" と Material Design Icons (mdi) を使用した Vuetify 設定
- `vite.config.js`: Laravel プラグイン、Vue、Vuetify 自動インポートで設定済み

### TypeScript 設定

- パスエイリアス: `@/*` は `resources/js/*` にマッピング
- Strict モード有効
- ターゲット: ESNext、Node 用モジュール解決

### Vuetify セットアップ

Vuetify は `plugins/vuetfly.ts` で以下のように設定されています:
- カスタムテーマ: "pinkTheme" (ピンクがプライマリ、ティールがセカンダリ)
- Material Design Icons (mdi) を `@mdi/font` 経由で使用
- カスタムレスポンシブブレークポイント (sm: 340px, md: 540px, lg: 768px, xl: 1080px, xxl: 1280px)
- Sass スタイルは `resources/sass/app.scss` 経由でインポート

**注意**: `app.js` でのインポートは `plugins/vuetfly.js` を参照していますが、実際のファイルは `plugins/vuetfly.ts` です。Vite が自動的に解決します。

### バックエンド構造

標準的な Laravel 構造:
- **コントローラー**: `app/Http/Controllers/` - Laravel Breeze 認証 + ProfileController
- **モデル**: `app/Models/` - Sanctum 認証を使用した User モデル
- **ミドルウェア**: `app/Http/Middleware/HandleInertiaRequests.php` - すべての Inertia ページにデータを共有
- **ルート**:
  - `routes/web.php` - メインアプリケーションルート
  - `routes/auth.php` - Laravel Breeze 認証ルート

### コードスタイル設定

**JavaScript/Vue/TypeScript** (ESLint + Prettier):
- 4スペースインデント
- シングルクォート、セミコロンなし
- Vue 3 推奨ルール
- テンプレートで1行につき最大1属性
- TypeScript strict モード
- フォーマット用 Prettier 統合

**PHP** (Laravel Pint):
- Laravel のデフォルト PSR-12 スタイル

**フォーマット詳細**:
- 行幅: 130文字
- 末尾カンマ必須
- HTML 空白文字の扱い: ignore

## コミットメッセージ規約

日本語で Conventional Commits に従います:
- `feat`: 新しい機能
- `fix`: バグの修正
- `docs`: ドキュメントのみの変更
- `style`: 空白、フォーマット、セミコロン追加など
- `refactor`: 仕様に影響がないコード改善(リファクタ)
- `perf`: パフォーマンス向上関連
- `test`: テスト関連
- `chore`: ビルド、補助ツール、ライブラリ関連

## Inertia での作業

新しいページを追加する際:
1. `resources/js/Pages/` に Vue コンポーネントを作成
2. `routes/web.php` に `Inertia::render('PageName', $props)` を使用してルートを追加
3. コントローラーから渡された props は Vue コンポーネントの props で自動的に利用可能
4. ナビゲーションには `@inertiajs/vue3` の `<Link>` コンポーネントを使用（SPA 動作を維持）
5. プログラムによるナビゲーションやフォーム送信には `router.visit()` や `router.post()` を使用

共有データ（認証済みユーザーなど）は、任意のコンポーネントで `usePage().props` 経由で利用可能です。

## テストデータベース

テストスイートは別の `testing` データベースを使用します（`phpunit.xml` を参照）。このデータベースが存在することを確認するか、テスト用に SQLite を設定してください。

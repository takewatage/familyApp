# リポジトリ構造定義書

## 1. ディレクトリ構造

```
family-app/
├── app/                              # アプリケーションコード（PHP）
│   ├── Dtos/                         # Data Transfer Objects（Spatie Laravel Data）
│   │   ├── Auth/                     # 認証・招待登録関連DTO（InviteConfirmResult, RegisterPageResult）
│   │   ├── Common/                   # 共通DTO
│   │   ├── Model/                    # モデル対応DTO（UserData, FamilyData, ExpenseData, QuickEntryData等）
│   │   ├── Family/                   # 家族設定・メンバー管理・切り替え関連DTO
│   │   ├── Task/                     # タスク関連リクエスト・レスポンスDTO
│   │   ├── Budget/                   # 家計簿関連DTO（Expense/Category/Shop/PaymentMethod/QuickEntry のRequest・Result）
│   │   └── MyPage/                   # マイページ関連DTO
│   ├── Events/                       # Laravelイベント（TaskUpdated, CategoryUpdated）
│   ├── Http/
│   │   └── Controllers/              # HTTPコントローラー
│   │       ├── Auth/                 # 認証関連コントローラー
│   │       └── Concerns/             # コントローラー共通トレイト（AuthorizesFamilyOwnership, ProvidesBudgetOptions）
│   ├── Models/                       # Eloquentモデル（User, Family, VirtualUser, Task等）
│   ├── Policies/                     # Laravel認可ポリシー（FamilyPolicy等）
│   └── Services/                     # ビジネスロジック（ImageUploadService, CurrentFamilyService, InviteUrlService等）
├── database/
│   ├── migrations/                   # DBマイグレーションファイル
│   ├── factories/                    # テスト用モデルファクトリー
│   └── seeders/                      # シーダー
├── resources/
│   ├── js/                           # フロントエンドコード（TypeScript/Vue）
│   │   ├── app.ts                    # エントリーポイント
│   │   ├── bootstrap.ts              # Bootstrap設定（Echo等）
│   │   ├── Api/                      # Axiosを使ったAPI通信クラス
│   │   ├── Components/               # Vueコンポーネント
│   │   │   ├── App/                  # アプリケーション全体コンポーネント
│   │   │   ├── Auth/                 # 認証関連コンポーネント
│   │   │   ├── Budget/               # 家計簿コンポーネント（ExpenseForm等）
│   │   │   ├── Common/               # 共通コンポーネント（再利用可能）
│   │   │   ├── Dok/                  # Dok機能コンポーネント
│   │   │   ├── Family/               # 家族設定・メンバー管理・切り替えコンポーネント
│   │   │   ├── FamilyTask/           # タスク関連コンポーネント
│   │   │   └── MyPage/               # マイページコンポーネント
│   │   ├── Composables/              # Vue3 Composition API（useXxx形式）
│   │   │   ├── Common/               # 共通Composables（useAppTheme, useSnackbar等）
│   │   │   ├── Dok/                  # Dok関連Composables
│   │   │   └── Task/                 # タスク関連Composables
│   │   ├── Constants/                # 定数定義（footerApps.ts: フッターアプリ定義）
│   │   ├── Layouts/                  # Inertiaページレイアウト
│   │   ├── Pages/                    # Inertiaページコンポーネント
│   │   │   ├── Auth/                 # 認証ページ
│   │   │   ├── Budget/               # 家計簿ページ（ExpenseIndex, Categories, Shops, PaymentMethods, QuickEntries）
│   │   │   ├── Dok/                  # Dokページ
│   │   │   ├── MyPage/               # マイページ（家族設定・設定・フッター設定ページ含む）
│   │   │   └── Task/                 # タスクページ
│   │   ├── Plugins/                  # Vueプラグイン設定（Vuetify等）
│   │   ├── Types/                    # TypeScript型定義
│   │   │   └── dto.generated.d.ts    # 自動生成型（php artisan typescript:transform）
│   │   └── Utils/                    # ユーティリティ関数
│   └── sass/
│       └── app.scss                  # グローバルスタイル
├── routes/
│   ├── web.php                       # Webルート（メイン）
│   ├── auth.php                      # 認証ルート
│   ├── channels.php                  # Broadcastチャネル定義
│   └── console.php                   # Artisanコマンド
├── tests/
│   ├── Feature/                      # フィーチャーテスト（HTTP通信レベル）
│   └── Unit/                         # ユニットテスト
├── docs/
│   ├── steering/                     # 永続化ドキュメント（本ファイル群）
│   ├── working/                      # 開発作業ドキュメント（{YYYYMMDD}_{要件名}/）
│   └── archive/                      # アーカイブ
├── .claude/                          # Claude Code 設定・プロジェクトガイドライン
│   ├── CLAUDE.md                     # プロジェクトインストラクション
│   ├── rules/                        # ルールファイル
│   │   ├── backend-rule.md           # バックエンドルール
│   │   └── front-rule.md             # フロントエンドルール
│   └── skills/                       # Claude Codeスキル
├── config/                           # Laravel設定ファイル
├── storage/                          # ファイルストレージ
├── .eslintrc.yml                     # ESLint設定
├── .prettierrc.yml                   # Prettier設定
├── composer.json                     # PHP依存管理
├── package.json                      # Node.js依存管理
├── phpunit.xml                       # PHPUnit設定
├── tsconfig.json                     # TypeScript設定
└── vite.config.js                    # Vite設定
```

## 2. 命名規則

### ファイル命名

| 対象                    | 規則         | 例                           |
|------------------------|-------------|------------------------------|
| Vueコンポーネント        | PascalCase  | `TaskListItem.vue`           |
| TypeScript（composable）| camelCase   | `useTask.ts`                 |
| TypeScript（util/const）| camelCase   | `dateUtils.ts`               |
| PHPクラス               | PascalCase  | `TaskController.php`         |
| PHPテスト               | PascalCase  | `TaskControllerTest.php`     |
| マイグレーション          | snake_case  | `2026_01_09_create_tasks_table.php` |

### ディレクトリ命名

| 対象                      | 規則        | 例            |
|--------------------------|------------|--------------|
| PHPディレクトリ（app配下） | PascalCase | `Controllers/`, `Services/` |
| フロントエンドディレクトリ  | PascalCase | `Components/`, `Composables/` |
| 開発作業ドキュメント        | snake_case | `20260324_task_feature/` |

## 3. 各ディレクトリの役割

### app/Dtos/

- **役割**: LaravelとVue間のデータ受け渡し型定義。Spatie Laravel Data を使用
- **命名**: リクエスト入力は `XXXRequestData`（例: `SaveTaskRequestData`）、レスポンスは `XXXData` or `XXXResult`
- **注意**: `#[TypeScript]` 属性を付けると `dto.generated.d.ts` に自動出力される

### app/Services/

- **役割**: コントローラーから切り出したビジネスロジック
- **配置するファイル**: 複数コントローラーで共通するロジック、外部API連携処理

### resources/js/Api/

- **役割**: Axiosを使ったバックエンドとのHTTP通信クラス
- **配置するファイル**: `client.ts`（共通設定）、各機能のAPI関数（`familyTaskApi.ts`等）
- **注意**: camelCase ↔ snake_case の自動変換は `client.ts` で処理済み

### resources/js/Composables/

- **役割**: Vue3 Composition APIを使った再利用可能なロジック
- **命名**: `use` + 機能名（例: `useTask.ts`、`useSnackbar.ts`）
- **主要ファイル**:
  - `Common/useAppTheme.ts`: Vuetify テーマ（ライト/ダーク/システム）とプライマリー・セカンダリーカラーを適用する。`THEMES` 定数（7プリセット）を export し、`AuthenticatedLayout.vue` から呼び出す。`$page.props.userSettings` を watch して即時反映

### resources/js/Types/

- **役割**: TypeScript型定義
- **注意**: `dto.generated.d.ts` は `php artisan typescript:transform` で自動生成される。Docker 未起動時は手動更新も可

### resources/js/Constants/

- **役割**: フロントエンド定数定義
- **主要ファイル**:
  - `footerApps.ts`: フッターナビゲーションに表示できるアプリ一覧（`FOOTER_APPS`）、デフォルト項目（`DEFAULT_FOOTER_ITEMS`）、必須項目（`REQUIRED_FOOTER_ITEMS`）、非表示ルート（`FOOTER_HIDDEN_ROUTES`）を定義。新しいアプリを追加する際はここに追記する

### docs/

- **steering/**: 永続化ドキュメント。プロダクトの信頼できる情報源として常に整合性を保つ
- **working/**: 開発作業ドキュメント。`{YYYYMMDD}_{要件名}/` の形式で管理
- **archive/**: 完了した開発作業ドキュメントのアーカイブ

# リポジトリ構造定義書

## 1. ディレクトリ構造

```
family-app/
├── app/                              # アプリケーションコード（PHP）
│   ├── Dtos/                         # Data Transfer Objects（Spatie Laravel Data）
│   │   ├── Model/                    # モデル対応DTO（UserData, FamilyData, TaskData等）
│   │   ├── Task/                     # タスク関連リクエスト・レスポンスDTO
│   │   └── MyPage/                   # マイページ関連DTO
│   ├── Events/                       # Laravelイベント（TaskUpdated, CategoryUpdated）
│   ├── Http/
│   │   └── Controllers/              # HTTPコントローラー
│   │       └── Auth/                 # 認証関連コントローラー
│   ├── Models/                       # Eloquentモデル
│   └── Services/                     # ビジネスロジック（ImageUploadService等）
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
│   │   │   ├── Common/               # 共通コンポーネント（再利用可能）
│   │   │   ├── Dok/                  # Dok機能コンポーネント
│   │   │   ├── FamilyTask/           # タスク関連コンポーネント
│   │   │   ├── Layout/               # レイアウトコンポーネント
│   │   │   └── MyPage/               # マイページコンポーネント
│   │   ├── Composables/              # Vue3 Composition API（useXxx形式）
│   │   │   ├── Common/               # 共通Composables
│   │   │   ├── Dok/                  # Dok関連Composables
│   │   │   └── Task/                 # タスク関連Composables
│   │   ├── Constants/                # 定数定義
│   │   ├── Layouts/                  # Inertiaページレイアウト
│   │   ├── Pages/                    # Inertiaページコンポーネント
│   │   │   ├── Auth/                 # 認証ページ
│   │   │   ├── Dok/                  # Dokページ
│   │   │   ├── MyPage/               # マイページ
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

### resources/js/Types/

- **役割**: TypeScript型定義
- **注意**: `dto.generated.d.ts` は `php artisan typescript:transform` で自動生成されるため手動編集禁止

### docs/

- **steering/**: 永続化ドキュメント。プロダクトの信頼できる情報源として常に整合性を保つ
- **working/**: 開発作業ドキュメント。`{YYYYMMDD}_{要件名}/` の形式で管理
- **archive/**: 完了した開発作業ドキュメントのアーカイブ

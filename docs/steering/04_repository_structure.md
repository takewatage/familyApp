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
│   │   ├── Calendar/                 # カレンダー関連DTO（CalendarPageResult, CalendarParticipantResult, CalendarEventResult, CalendarEventRequest, CalendarLabelResult 等）
│   │   ├── Budget/                   # 家計簿関連DTO（Expense/Category/Shop/PaymentMethod/QuickEntry/RecurringExpense/Budget設定 のRequest・Result）
│   │   └── MyPage/                   # マイページ関連DTO
│   ├── Console/
│   │   └── Commands/                 # Artisanコマンド実装（GenerateRecurringExpenses, CheckBudgetAlerts, CreateFamily等）
│   ├── Events/                       # Laravelイベント（TaskUpdated, CategoryUpdated）
│   ├── Exceptions/                   # 独自例外（SocialAuthException: Google認証の失敗 / InvalidInviteException: 招待経由の新規登録不可）
│   ├── Http/
│   │   └── Controllers/              # HTTPコントローラー
│   │       ├── Auth/                 # 認証関連コントローラー（AuthenticatedSession, RegisteredUser, GoogleAuth等）
│   │       └── Concerns/             # コントローラー共通トレイト（AuthorizesFamilyOwnership, ProvidesBudgetOptions）
│   ├── Models/                       # Eloquentモデル（User, Family, VirtualUser, Task等）
│   ├── Policies/                     # Laravel認可ポリシー（FamilyPolicy等）
│   ├── Services/                     # ビジネスロジック（CurrentFamilyService, FamilyMemberService, CalendarEventService, CalendarLabelService, CalendarSettingsService, FamilyProvisionService, SocialAuthService, ExpenseService, RecurringExpenseGenerator, RecurringExpenseService, BudgetService, BudgetCalculationService, BudgetAlertService, RecurringExpenseReminderService, PwaManifestService等）
│   │   └── Concerns/                 # サービス共通トレイト（ValidatesFamilyMember）
│   └── Support/                      # 共通ヘルパー（BudgetScopeRules: カテゴリー family スコープ validation / CalendarRecurrence: 予定の RRULE の検証・展開）
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
│   │   │   ├── Calendar/             # スワイプカレンダー（SwipeCalendar, MonthGrid, DayCell, 各種Sheet, EventEditForm, RecurrenceField, RecurrenceScopeDialog, ParticipantSelectSheet, ParticipantAvatars, LabelSelectSheet, LabelEditForm, CalendarSettingsSheet）
│   │   │   ├── Common/               # 共通コンポーネント（再利用可能。PwaUpdateNotifier, DatePickerDialog, TimePickerDialog, PickerField 等）
│   │   │   ├── Dok/                  # Dok機能コンポーネント
│   │   │   ├── Family/               # 家族設定・メンバー管理・切り替えコンポーネント（FamilyPwaSettingsForm 等）
│   │   │   ├── FamilyTask/           # タスク関連コンポーネント
│   │   │   └── MyPage/               # マイページコンポーネント
│   │   ├── Composables/              # Vue3 Composition API（useXxx形式）
│   │   │   ├── Common/               # 共通Composables（useAppTheme, useSnackbar等）
│   │   │   ├── Budget/               # 家計簿関連Composables（useMonthNavigation: 月切替共通ロジック）
│   │   │   ├── Calendar/             # カレンダー関連Composables（useCalendar: 月グリッド生成 / useSwipe: 横スワイプ）
│   │   │   ├── Dok/                  # Dok関連Composables
│   │   │   └── Task/                 # タスク関連Composables
│   │   ├── Constants/                # 定数定義（footerApps.ts: フッターアプリ定義 / calendarColors.ts: 予定のカラーパレット・誕生日の色）
│   │   ├── Layouts/                  # Inertiaページレイアウト
│   │   ├── Pages/                    # Inertiaページコンポーネント
│   │   │   ├── Auth/                 # 認証ページ
│   │   │   ├── Budget/               # 家計簿ページ（ExpenseIndex, Categories, Shops, PaymentMethods, QuickEntries, RecurringExpenses, BudgetSettings, Dashboard）
│   │   │   ├── Calendar/             # カレンダー画面（Index: モックアップ）・動作確認ページ（Demo・メニュー非掲載）
│   │   │   ├── Dok/                  # Dokページ
│   │   │   ├── MyPage/               # マイページ（家族設定・設定・フッター設定・アプリインストール案内ページ含む）
│   │   │   └── Task/                 # タスクページ
│   │   ├── Plugins/                  # Vueプラグイン設定（Vuetify等）
│   │   ├── Types/                    # TypeScript型定義
│   │   │   ├── calendar.ts           # カレンダーの手書き型（CalendarEvent, EventMap, DayCellData等）
│   │   │   └── dto.generated.d.ts    # 自動生成型（php artisan typescript:transform）
│   │   └── Utils/                    # ユーティリティ関数（dateFormatter: 表示整形 / calendarDate: 日付計算 / calendarColor: 予定の色解決）
│   └── sass/
│       └── app.scss                  # グローバルスタイル
├── routes/
│   ├── web.php                       # Webルート（メイン）
│   ├── auth.php                      # 認証ルート
│   ├── channels.php                  # Broadcastチャネル定義
│   ├── console.php                   # Artisanコマンド
│   └── e2e.php                       # E2E 用テストデータ作成ルート（APP_ENV=e2e のときだけ /__e2e に登録）
├── tests/
│   ├── Feature/                      # フィーチャーテスト（HTTP通信レベル）
│   │   └── Pwa/                      # PWA（/sw.js・manifest・家族のアプリ設定・インストール案内）
│   └── Unit/                         # ユニットテスト
├── e2e/                              # E2E テスト（Playwright）
│   ├── auth/                         # ログイン・招待経由の新規登録
│   ├── calendar/                     # カレンダー（予定の保存・繰り返し・誕生日・ラベル・参加者・高さ固定）
│   ├── support/                      # テストデータ作成（/__e2e/families・/__e2e/calendar-events）・ログイン・カレンダー操作のヘルパー
│   └── global-setup.ts               # 実行前に E2E 専用 DB（testing_e2e）を作り直す
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
├── config/                           # Laravel設定ファイル（pwa.php: SW パス・manifest 既定値）
├── public/
│   └── icons/                        # PWA の既定アイコン（apple-touch-icon / 192 / 512 / maskable 512）
├── storage/                          # ファイルストレージ
├── .eslintrc.yml                     # ESLint設定
├── .prettierrc.yml                   # Prettier設定
├── composer.json                     # PHP依存管理
├── package.json                      # Node.js依存管理
├── phpunit.xml                       # PHPUnit設定
├── playwright.config.ts              # Playwright（E2E）設定
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
- **主要ファイル**:
  - `FamilyMemberService.php`: 家族のメンバー・仮想ユーザーをアバター付き DTO（`FamilyMemberData` / `VirtualUserData`）に変換する。並び順は参加順・作成順で固定。ホーム・メンバー管理・カレンダー（参加者）で共通利用
  - `PwaManifestService.php`: 家族設定 `families.settings.pwa` と `config/pwa.php` の既定値をマージし、`FamilyPwaSettingsData`（`resolve`）・Web App Manifest（`manifest`）を返す（内容ハッシュは `FamilyPwaSettingsData.manifest_hash` に含める）
  - `ImageUploadService.php`: 画像のリサイズ・アップロード。`uploadAppIcon()` は PWA アプリ画像（PNG 180 / 192 / 512 / maskable 512）を生成。途中で失敗したらアップロード済みのサイズを削除する。`deleteQuietly()` は削除失敗をログに残すだけで例外を投げない（後片付け用）

### app/Http/Controllers/（PWA 関連）

- `ServiceWorkerController.php`: `GET /sw.js`。`public/build/sw.js` をサイト直下から配信（`web` ミドルウェア外）
- `PwaManifestController.php`: `GET /manifest.webmanifest`。動的 manifest
- `FamilySettingsController.php`: `updatePwa` / `destroyPwaIcon` で家族のアプリ名・アプリ画像を更新
- `MyPageController.php`: `appInstall` でインストール案内ページ

### resources/js/Api/

- **役割**: Axiosを使ったバックエンドとのHTTP通信クラス
- **配置するファイル**: `client.ts`（共通設定）、各機能のAPI関数（`familyTaskApi.ts`等）
- **注意**: camelCase ↔ snake_case の自動変換は `client.ts` で処理済み
  - `client` は `axios.create()` のインスタンスのため、`app.ts` でグローバル `axios` に登録した interceptor は**効かない**。変換は `client.ts` 自身の request / response interceptor で行っている
  - API 関数は必ず `client` を使い、新たに `axios.create()` しないこと

### resources/js/Composables/

- **役割**: Vue3 Composition APIを使った再利用可能なロジック
- **命名**: `use` + 機能名（例: `useTask.ts`、`useSnackbar.ts`）
- **主要ファイル**:
  - `Common/useAppTheme.ts`: Vuetify テーマ（ライト/ダーク/システム）とプライマリー・セカンダリーカラーを適用する。`THEMES` 定数（7プリセット）を export し、`AuthenticatedLayout.vue` から呼び出す。`$page.props.userSettings` を watch して即時反映。`<meta name="theme-color">` もプライマリーカラーに更新する
  - `Common/usePwaMeta.ts`: `setupPwaMeta()` を `app.ts` で一度だけ呼ぶ。Inertia の `navigate` イベントで共有プロパティ `pwa` を見て `<head>` の manifest / apple-touch-icon / `apple-mobile-web-app-title` を差し替える（家族切り替え・ログアウト対応。レイアウト非依存）
  - `Common/usePwaUpdate.ts`: `startPwaUpdate()` を `app.ts` で一度だけ呼び、Service Worker（`/sw.js`）の登録と新バージョン検知（1 時間ごと・`visibilitychange` で更新チェック）を行う。本番ビルドのみ登録。`usePwaUpdate()` は検知状態（モジュール共有の `needRefresh`）と操作を返す
  - `Common/usePwaInstall.ts`: `beforeinstallprompt` の捕捉（`app.ts` で先読み）、インストール済み判定、プラットフォーム判定（iPadOS 対応）
  - `Calendar/useCalendar.ts`: カレンダーの表示年月・選択日の状態と月グリッド（前月埋め + 当月 + 翌月埋め。常に 6 週・42 マス）の生成。クライアント内で完結する月移動を担う
  - `Calendar/useSwipe.ts`: 横スワイプの指追従（`transform: translateX`）としきい値によるページ送り判定。縦スクロールと競合しないよう最初の動きで軸をロックする
  - **注意**: `Budget/useMonthNavigation.ts`（Inertia 遷移でサーバーから取り直す月切替）と `Calendar/useCalendar.ts`（クライアント内の月移動）は役割が異なるため統合しない

### resources/js/Types/

- **役割**: TypeScript型定義
- **主要ファイル**:
  - `global.d.ts`: グローバル型。ビルド時定数 `__APP_VERSION__` / `__APP_BUILD_ID__` を宣言
  - `vite-env.d.ts`: Vite / `vite-plugin-pwa/vue`（`virtual:pwa-register/vue`）の型参照
  - `calendar.ts`: カレンダーコンポーネントの手書き型（`CalendarEvent` / `CalendarParticipant` / `CalendarLabel` / `EventMap` / `DayCellData` / `YearMonth` 等）。予定はサーバーとやり取りしないため DTO 自動生成の対象外（参加者の選択肢は `CalendarParticipantResult` から変換する）
- **注意**: `dto.generated.d.ts` は `php artisan typescript:transform` で自動生成される。Docker 未起動時は手動更新も可

### resources/js/Constants/

- **役割**: フロントエンド定数定義
- **主要ファイル**:
  - `footerApps.ts`: フッターナビゲーションに表示できるアプリ一覧（`FOOTER_APPS`）、デフォルト項目（`DEFAULT_FOOTER_ITEMS`）、必須項目（`REQUIRED_FOOTER_ITEMS`）、非表示ルート（`FOOTER_HIDDEN_ROUTES`）を定義。新しいアプリを追加する際はここに追記する
  - `appVersion.ts`: ビルド時に埋め込まれたバージョン（`APP_VERSION` / `APP_BUILD_ID` / 表示用 `APP_VERSION_LABEL`）

### docs/

- **steering/**: 永続化ドキュメント。プロダクトの信頼できる情報源として常に整合性を保つ
- **working/**: 開発作業ドキュメント。`{YYYYMMDD}_{要件名}/` の形式で管理
- **archive/**: 完了した開発作業ドキュメントのアーカイブ

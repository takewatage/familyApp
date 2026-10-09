# ユビキタス言語定義書

## 概要

このドキュメントでは、Family App プロダクト内で使用する用語を統一的に定義します。
コード内の変数名・型名・関数名もこの定義に従ってください。

## 用語一覧

### ビジネスドメイン用語

| 用語                | 英語表記           | 定義                                | コード内の使用例                                 |
|-------------------|----------------|-----------------------------------|------------------------------------------|
| ユーザー              | User           | Family App を利用する個人。メールアドレスで識別される  | `User` モデル、`UserData` DTO                |
| 家族                | Family         | 複数のユーザーが共有する家族グループ。ユニークなコードで識別される | `Family` モデル、`FamilyData` DTO            |
| 家族コード             | Family Code    | 家族グループへの**招待**に使う8文字のユニークコード。有効期限付きで再生成できる。**ログイン時の入力は不要**（認証条件ではない） | `Family.code`                            |
| 現在の家族             | Current Family | セッションで選択中の家族。ログイン時に `users.last_family_id` → 参加日時が最も古い家族の順で自動決定される | `CurrentFamilyService`, `users.last_family_id` |
| Google連携            | Google Account Link | Googleアカウントとアプリのユーザーの紐付け。Google側でメール確認済みの場合のみ既存アカウントへ自動連携する | `User.google_id`, `SocialAuthService`    |
| オーナー              | Owner          | 家族グループを作成したユーザー（`family_user.role = 'owner'`）。家族設定の編集権限を持つ。家族の新規作成は管理者が `family:create` コマンドで行う | `Family.owner_id`, `family_user.role`, `CreateFamily` コマンド |
| 招待経由登録           | Invite-only Registration | 新規登録は家族の招待URL経由のみ許可する方針（個人利用のため）。招待なし・無効／期限切れ・定員到達では登録できない（メール登録・Google登録とも） | `FamilyProvisionService::requireValidInviteFamily()`, `InvalidInviteException` |
| タスク               | Task           | 家族が管理するToDoアイテム                   | `Task` モデル、`TaskData` DTO                |
| カテゴリー             | TaskCategory   | タスクを分類するグループ。例: 「家事」「買い物」         | `TaskCategory` モデル                       |
| 完了タスク             | Completed Task | `is_completed = true` のタスク        | `Task.is_completed`, `Task.completed_at` |
| ドク（Dok）(どっちがお得かね) | Dok            | 日用品・買い物の価格を比較する機能                 | `DokController`, `Pages/Dok/`            |
| アバター              | Avatar         | ユーザーのプロフィール画像                     | `User.files`（collection: 'avatar'）       |
| ユーザー設定           | UserSettings   | ユーザーごとの個別設定（テーマ・テーマカラー）。`users.settings` JSONカラムに保存。スキーマ: `{ theme, theme_color }` | `User.settings`, `UserSettingsResult` DTO |
| テーマカラー           | ThemeColor     | プライマリカラーのプリセット。`pink` / `blue` / `purple` / `green` / `orange` / `teal` から選択 | `UserSettings.theme_color` |
| 仮想ユーザー           | VirtualUser    | アプリにログインせずにタスク等の担当者として割り当てられる家族内の人物。`virtual_users` テーブルで管理 | `VirtualUser` モデル、`VirtualUserData` DTO |
| アクティブ家族         | ActiveFamily   | ユーザーが現在操作対象としている家族グループ。`users.settings.activeFamilyId` に保存 | `User.settings['activeFamilyId']` |
| ファイル              | File           | ポリモーフィックに各モデルに紐付けられるファイルリソース      | `File` モデル                               |
| カレンダーイベント       | CalendarEvent  | カレンダーの1日に表示する1件の項目（タイトル・色・時刻・アイコン・参加者）。カレンダー自身は永続化せず、利用側が渡す | `CalendarEvent` 型、`Components/Calendar/` |
| ラベル（予定）          | CalendarLabel  | 予定に付ける名前＋カラーの組。家族で共有し、予定の色はラベルのカラーで決まる。名前・カラー・並び順を家族全員が編集できる | `calendar_labels` テーブル、`CalendarLabel` 型、`EventEditModel.labelId` |
| 繰り返し予定           | Recurring Event | RRULE（RFC 5545）で繰り返す予定。変更・削除は「この予定のみ（this）／これ以降（following）／すべて（all）」の範囲で行う | `calendar_events.rrule`、`CalendarRecurrence`、`RecurrenceScope` |
| 上書き予定             | Override       | 繰り返し予定の 1 回だけを変更したときに作る予定。繰り返し元（`recurring_event_id`）と本来の発生日（`original_date`）を持つ | `calendar_events.recurring_event_id` / `original_date` |
| 除外日                | EXDATE         | 繰り返し予定の 1 回だけを削除した日 | `calendar_event_exdates` |
| 発生日                | Occurrence Date | 繰り返し予定のある 1 回の本来の日付。変更・削除でどの回かを指定するのに使う | `CalendarEventResult.occurrenceDate` |
| 参加者（予定）          | Participant    | 予定に関係する家族メンバー（ログインユーザー・仮想ユーザー）。予定の右端にアイコンで表示する | `CalendarParticipant` 型、`EventEditModel.participantIds`、`CalendarParticipantResult` |
| 日付キー              | DateKey        | イベントを日付ごとに引くためのキー。`YYYY-MM-DD` 形式の文字列 | `DateKey` 型、`toDateKey()` |
| イベントマップ          | EventMap       | 日付キーごとにカレンダーイベント配列を引けるマップ（`{ '2026-08-01': [...] }`） | `EventMap` 型、`SwipeCalendar` の `events` prop |
| 月グリッド             | MonthGrid      | 1ヶ月分の7列グリッド。前月・翌月の日付で前後を埋め（当月外セル）、月によらず常に 6 週（42 マス）で表示する | `MonthGrid.vue`、`DayCellData.isOtherMonth` |
| アプリ設定（ホーム画面） | Family PWA Settings | 家族ごとの PWA 外観（アプリ名・アプリ画像）。`families.settings.pwa` に保存。オーナーのみ変更可。未設定項目は既定値（アプリ名 `familyApp`・既定アイコン） | `FamilyPwaSettingsData` DTO, `UpdateFamilyPwaSettingsRequest`, `PwaManifestService` |
| アプリ画像            | App Icon       | ホーム画面・スプラッシュに使うアイコン。アップロード画像から PNG 180（apple-touch-icon）/ 192 / 512 / maskable 512 を生成 | `families.settings.pwa.icon`, `ImageUploadService::uploadAppIcon()` |
| アプリバージョン        | App Version    | 利用者向けのバージョン表記 `v{version} ({ビルドID})`。番号の SSoT は `package.json` の `version` | `__APP_VERSION__`, `__APP_BUILD_ID__`, `APP_VERSION_LABEL` |
| ソート順              | Sort           | タスク・カテゴリーの表示順を管理する整数値             | `Task.sort`, `TaskCategory.sort`         |

### 技術用語

| 用語              | 定義                                                     | コード内の使用例                              |
|-----------------|--------------------------------------------------------|---------------------------------------|
| DTO             | Data Transfer Object。PHPとフロントエンド間のデータ受け渡し型定義           | `app/Dtos/`、`SaveTaskRequestData`     |
| Inertia         | LaravelとVueをSPAのように繋ぐブリッジライブラリ                         | `Inertia::render()`, `useInertiaForm` |
| 自動生成型           | `php artisan typescript:transform` で生成されるTypeScript型定義 | `dto.generated.d.ts`                  |
| camelCase変換     | PHPのsnake_case ↔ TypeScriptのcamelCaseを自動変換する仕組み        | `CamelCaseMapper`、`client.ts`         |
| Private Channel | Pusherの認証付きWebSocketチャネル。`family.{family_id}` で家族単位に分離 | `channels.php`                        |
| Composable      | Vue3 Composition APIの再利用可能なロジック。`use` プレフィックス付き        | `useTask.ts`, `useSnackbar.ts`        |
| PWA             | Progressive Web App。ホーム画面に追加して全画面で起動できる Web アプリ | `vite-plugin-pwa`, `/manifest.webmanifest` |
| Service Worker（SW） | ブラウザがページとは別に動かすスクリプト。ビルド成果物のキャッシュと新バージョン検知に使う。`/sw.js` で配信 | `ServiceWorkerController`, `usePwaUpdate` |
| Web App Manifest | アプリ名・アイコン・起動 URL 等を記述した JSON。家族設定から動的生成 | `PwaManifestController`, `PwaManifestService` |
| manifest ハッシュ | manifest と apple-touch-icon の内容ハッシュ。`<link rel="manifest">` の `?v=` と家族切り替え時の差し替え判定に使う | `FamilyPwaSettingsData.manifest_hash` |

## 命名規則

### 変数・関数名

- **PHP**: snake_case（例: `$family_id`, `update_profile()`）
- **TypeScript/JavaScript**: camelCase（例: `familyId`, `updateProfile()`）

### 型・クラス名

- **PHP**: PascalCase（例: `TaskController`, `SaveTaskRequestData`）
- **TypeScript**: PascalCase（例: `TaskData`, `UserData`）

### Vueコンポーネント名

- PascalCase（例: `TaskListItem.vue`, `CategoryEditDialog.vue`）

### ファイル名

- **PHPクラス**: PascalCase（例: `TaskController.php`）
- **Vueコンポーネント**: PascalCase（例: `FamilyTask.vue`）
- **TypeScript（composable）**: camelCase + `use` プレフィックス（例: `useTask.ts`）
- **TypeScript（util/const）**: camelCase（例: `dateUtils.ts`）
- **マイグレーション**: snake_case + タイムスタンプ（例: `2026_01_09_create_tasks_table.php`）

## 使用を避ける表現

| 避けるべき表現      | 正しい表現          | 理由                             |
|--------------|----------------|--------------------------------|
| `todo`       | `task`         | プロダクト内の用語は「タスク（Task）」に統一       |
| `group`      | `family`       | 家族グループは「Family」に統一             |
| `category`   | `taskCategory` | コード上はタスクに紐付くことを明示する            |
| `avatar_url` | `File`モデル参照    | アバターはFileモデルのポリモーフィックリレーションで管理 |

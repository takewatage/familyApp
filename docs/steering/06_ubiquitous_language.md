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
| 家族コード             | Family Code    | 家族グループへの招待に使う8文字のユニークコード          | `Family.code`                            |
| オーナー              | Owner          | 家族グループを作成したユーザー                   | `Family.owner_id`                        |
| タスク               | Task           | 家族が管理するToDoアイテム                   | `Task` モデル、`TaskData` DTO                |
| カテゴリー             | TaskCategory   | タスクを分類するグループ。例: 「家事」「買い物」         | `TaskCategory` モデル                       |
| 完了タスク             | Completed Task | `is_completed = true` のタスク        | `Task.is_completed`, `Task.completed_at` |
| ドク（Dok）(どっちがお得かね) | Dok            | 日用品・買い物の価格を比較する機能                 | `DokController`, `Pages/Dok/`            |
| アバター              | Avatar         | ユーザーのプロフィール画像                     | `User.files`（collection: 'avatar'）       |
| ユーザー設定           | UserSettings   | ユーザーごとの個別設定（テーマ等）。`users.settings` JSONカラムに保存 | `User.settings`, `UserSettingsResult` DTO |
| ファイル              | File           | ポリモーフィックに各モデルに紐付けられるファイルリソース      | `File` モデル                               |
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

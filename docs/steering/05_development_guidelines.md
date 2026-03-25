# 開発ガイドライン

## 1. コーディング規約

### 基本方針

- バックエンドは `.claude/rules/backend-rule.md` を、フロントエンドは `.claude/rules/front-rule.md` を参照
- 型安全を最優先とし、`any` 型の使用を避ける
- DTOを通じてバックエンド・フロントエンド間の型を一致させる

### lint・フォーマット

| ツール          | 設定ファイル         | 実行コマンド                        |
|---------------|--------------------|------------------------------------|
| ESLint        | `.eslintrc.yml`    | `npm run lint`（変更ファイルのみ）    |
| Prettier      | `.prettierrc.yml`  | `npm run format`（変更ファイルのみ） |
| Laravel Pint  | `pint.json`        | `php artisan pint`                  |

### バックエンド規約（詳細: `.claude/rules/backend-rule.md`）

- コントローラーのリクエスト入力・レスポンス返却は **必ず Spatie Laravel Data の Data クラス** を使用する
- リクエスト入力クラス命名: `XXXRequestData`（例: `SaveTaskRequestData`）
- レスポンス返却クラス命名: `XXXResult` or `XXXData`（例: `TaskPageData`）
- バリデーションは DTOの `rules()` メソッドで定義する
- camelCase変換: `#[MapInputName(CamelCaseMapper)]` / `#[MapOutputName(CamelCaseMapper::class)]` を使用
- TypeScript型自動生成: `#[TypeScript]` 属性を付与する

### フロントエンド規約（詳細: `.claude/rules/front-rule.md`）

- UIコンポーネントは **Vuetify を積極利用** する
- Inertiaフォームには `useInertiaForm` composable を使用する
- 型は `resources/js/Types/dto.generated.d.ts` の自動生成型を使用する
- `if` 文の `{}` は省略禁止
- `if` 文の `}` の後は1行開ける

## 2. テスト方針

### テスト種別

| 種別             | フレームワーク   | 実行コマンド           | 方針                              |
|----------------|---------------|----------------------|----------------------------------|
| ユニットテスト    | PHPUnit       | `php artisan test`   | サービスクラス等の単体ロジックをテスト |
| フィーチャーテスト | PHPUnit       | `php artisan test`   | HTTP通信レベルでのAPIテスト         |
| E2Eテスト        | （未導入）      | -                    | 手動テストで代替                    |

### テストの基本方針

- 可能な限り手動操作で確認し、操作で確認できない項目のみ自動テストを記載する
- テストでバグを見つけた場合は、AIにバグ内容を伝えて修正を依頼する
- テスト用DB: SQLite（`phpunit.xml` の `DB_DATABASE=testing`）
- テスト時のメール送信: `MAIL_MAILER=array`（実際には送信されない）

### テストファイル配置

- `tests/Unit/`: サービス・ユーティリティ等のユニットテスト
- `tests/Feature/`: コントローラーレベルのフィーチャーテスト

## 3. Git運用

### ブランチ戦略

- `main`: メインブランチ（常にデプロイ可能な状態）
- 機能開発: `feature/{機能名}` ブランチを切って開発
- バグ修正: `fix/{バグ内容}` ブランチを切って修正

### コミットメッセージ規約

```
{type}: {変更内容（日本語）}

例:
feat: タスク完了トグル機能を追加
fix: カテゴリー削除時のリアルタイム同期バグを修正
refactor: TaskController のロジックを Service に移動
chore: .gitignore を更新
```

主なtype:
- `feat`: 新機能
- `fix`: バグ修正
- `refactor`: リファクタリング
- `chore`: ビルド・設定変更
- `docs`: ドキュメント更新

## 4. 開発プロセス

### 基本フロー

1. 開発作業ドキュメントを生成する（`generate-working-docs` スキル）
2. 要件定義書・設計書の内容を確認する
3. タスクリストに従って AI にコード実装を依頼する
4. テスト手順書に従ってテストを実施する
5. バグがあれば AI に修正を依頼する
6. 完了後、永続化ドキュメントを更新する

### TypeScript型の更新フロー

PHPのDTOを変更した場合は以下を実行して型定義を同期する:

```bash
sail artisan typescript:transform
```

### レビュー手順

- セルフレビューを行い、バックエンド・フロントエンドルールに準拠しているか確認する
- プルリクエストを作成し、CI/CDが通ることを確認する

## 5. ドキュメント更新ルール

1. **機能追加時**: `02_functional_design.md` の画面一覧・詳細を更新し、`01_product_requirements.md` の機能一覧も更新
2. **API追加/変更時**: `03_architecture_specifications.md` のAPI一覧を更新
3. **型定義変更時**: `06_ubiquitous_language.md` を確認し、必要に応じて更新
4. **ディレクトリ/ファイル追加時**: `04_repository_structure.md` のディレクトリ構造を更新
5. **実装状態の変更時**: 各ドキュメントの「状態」欄を更新（✅完了、⚠️一部完了、📝計画中）

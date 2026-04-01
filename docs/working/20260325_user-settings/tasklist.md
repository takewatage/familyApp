# タスクリスト - ユーザー個別設定編集機能

## 進捗サマリー

| 状態   | 件数 |
|--------|------|
| 完了   | 6    |
| 進行中 | 0    |
| 未着手 | 0    |

## タスク一覧

### T-1: 要件定義・設計 ✅

- [x] 要件定義書の作成
- [x] 設計書（モックアップ・DB・API設計）の作成
- [x] レビュー完了

### T-2: DBマイグレーション・Modelの修正 ✅

- [x] `users` テーブルへの `settings` JSON カラム追加マイグレーション作成
  - `database/migrations/2026_03_31_000000_add_settings_to_users_table.php`
- [x] `User` モデルに `$casts` 追加（`'settings' => 'array'`）・`$fillable` に `settings` 追加
- [x] マイグレーション実行・動作確認

### T-3: バックエンド実装 ✅

- [x] `UserSettingsResult` DTO 作成（`app/Dtos/MyPage/UserSettingsResult.php`）
- [x] `UpdateSettingsRequest` DTO 作成（`app/Dtos/MyPage/UpdateSettingsRequest.php`）
- [x] `MyPageController@updateSettings` メソッド実装
- [x] `MyPageController@index` に `settings` props 追加（`MyPageData` に `settings` フィールド追加）
- [x] `routes/web.php` に `POST /mypage/settings` ルート追加
- [x] TypeScript 型の自動生成実行（`sail artisan typescript:transform`）

### T-4: フロントエンド実装 ✅

- [x] `UserSettingsForm.vue` コンポーネント作成（`resources/js/Components/MyPage/`）
- [x] `Pages/MyPage/index.vue` に `UserSettingsForm` を組み込む
- [x] 設定の初期値（`settings` props）を正しく渡す
- [x] スナックバーによる保存成功・エラー通知の動作確認

### T-5: テスト・品質確認 ✅

- [x] テスト手順書（`testing.md`）に沿って手動テスト実施
- [x] `sail yarn lint` でフロントエンドの lint エラーがないこと確認
- [x] `sail artisan pint` で PHP フォーマットが通ること確認

### T-6: ドキュメント更新 ✅

- [x] `docs/steering/01_product_requirements.md` に F-009 として追記
- [x] `docs/steering/02_functional_design.md` の S-008 に設定セクション追記
- [x] `docs/steering/03_architecture_specifications.md` に API・DB 変更を反映
- [x] `docs/steering/06_ubiquitous_language.md` に `UserSettings`・`settings` を追加

## 完了条件

- [x] 全タスクが完了
- [x] テスト手順書の全ケースが OK
- [x] 永続化ドキュメントが更新済み

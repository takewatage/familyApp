# タスクリスト - 家族設定機能

## 進捗サマリー

| 状態 | 件数 |
|------|------|
| 完了 | 12 |
| 進行中 | 0 |
| 未着手 | 2 |

## タスク一覧

### T-1: 要件定義・設計

- [x] 設計書の作成（design.md）
- [x] 要件定義書の作成（requirements.md）

### T-2: DB・マイグレーション

- [x] `families` テーブルに `code_expires_at` カラム追加マイグレーション
- [x] `virtual_users` テーブル作成マイグレーション

### T-3: バックエンド実装

- [x] `VirtualUser` モデル作成
- [x] `FamilyPolicy` 作成・登録
- [x] `FamilySettingsController` 実装（家族設定変更・コード再生成）
- [x] `FamilyMemberController` 実装（メンバー一覧・除名）
- [x] `VirtualUserController` 実装（仮想ユーザー CRUD）
- [x] `FamilySwitchController` 実装（家族切り替え）
- [x] `routes/web.php` にルート追加

### T-4: フロントエンド実装

- [x] マイページに家族設定メニュー追加
- [x] `FamilySettings.vue` 実装（家族設定変更ページ）
- [x] `FamilyMembers.vue` 実装（メンバー管理ページ）
- [x] `FamilySwitch.vue` 実装（家族切り替えページ）
- [x] `VirtualUserDialog.vue` 実装（仮想ユーザー追加・編集ダイアログ）

### T-5: バグ修正・追加タスク

- [x] 仮想ユーザー追加ダイアログの追加ボタンのレイアウト崩れを修正
- [x] 仮想ユーザー削除時に確認ダイアログを表示してから削除する

### T-6: テスト・ドキュメント

- [ ] testing.md に沿った手動テスト実施
- [ ] 永続化ドキュメント（steering/）更新確認

## 完了条件

- [ ] 全タスクが完了
- [ ] testing.md の全手動テストケースが OK
- [ ] 永続化ドキュメントが更新済み

# 要件定義書 - 家族設定機能

## 概要

マイページに家族設定メニューを追加し、家族設定変更・メンバー管理・家族切り替えの3機能を実装する。

## 背景・目的

家族グループの設定をアプリ内で完結して管理できるようにする。オーナーが家族名や招待コードを管理し、仮想ユーザーを使ってアプリ未登録の家族メンバー（子どもなど）をタスク担当者として扱えるようにする。また、複数の家族グループに所属するユーザーが操作対象を切り替えられるようにする。

## 要件一覧

### 機能要件

#### F-010: 家族設定変更

- **説明**: オーナーが家族グループの基本情報（家族名・最大メンバー数・招待コード）を変更する
- **受け入れ条件**:
  - [ ] 家族名を変更できる（オーナーのみ）
  - [ ] 最大メンバー数を変更できる（オーナーのみ）
  - [ ] 家族コードを再生成できる（有効期限：無期限・24時間・7日間・カスタム）
  - [ ] オーナー以外は閲覧のみ（フォーム読み取り専用、保存ボタン非表示）
  - [ ] 有効期限切れのコードで参加しようとした場合はエラーを返す

#### F-011: メンバー管理

- **説明**: 実ユーザーの一覧表示・除名と、仮想ユーザーの追加・編集・削除を行う
- **受け入れ条件**:
  - [ ] 実メンバー一覧（名前・アバター・ロール）を表示できる
  - [ ] メンバーを除名できる（オーナーのみ、自分自身は除名不可）
  - [ ] 招待コードを表示・コピーできる
  - [ ] 仮想ユーザーを追加できる（名前必須、アバター任意）
  - [ ] 仮想ユーザーを編集できる
  - [ ] 仮想ユーザーを削除できる（確認ダイアログあり）
  - [ ] 仮想ユーザー追加ダイアログの追加ボタンのレイアウトが正しく表示される

#### F-012: 家族切り替え

- **説明**: 複数の家族グループに所属するユーザーが操作対象の家族を切り替える
- **受け入れ条件**:
  - [ ] 所属している家族グループ一覧を表示できる
  - [ ] 選択した家族をアクティブ家族に切り替えられる
  - [ ] 切り替え後にホーム画面へ遷移する

### 非機能要件

- **セキュリティ**: 家族設定変更・メンバー除名はオーナーのみ可。Policy で制御する
- **保守性**: 権限チェックは FamilyPolicy に集約する

## スコープ

### 対象

- マイページへの家族設定メニュー追加
- 家族設定変更ページ（S-011）
- メンバー管理ページ（S-012）
- 家族切り替えページ（S-013）
- 仮想ユーザー CRUD API
- 仮想ユーザー追加ダイアログのレイアウト修正
- 仮想ユーザー削除時の確認ダイアログ

### 対象外

- オーナー変更
- 家族脱退
- 仮想ユーザーのタスクへの割り当て（将来対応）
- QRコード表示

## 実装対象ファイル（予定）

- `app/Http/Controllers/FamilySettingsController.php`
- `app/Http/Controllers/FamilyMemberController.php`
- `app/Http/Controllers/VirtualUserController.php`
- `app/Http/Controllers/FamilySwitchController.php`
- `app/Models/VirtualUser.php`
- `app/Models/Family.php`（リレーション追加）
- `app/Dtos/Family/FamilySettingsData.php`
- `app/Dtos/Family/UpdateFamilySettingsRequestData.php`
- `app/Dtos/Family/FamilyMemberData.php`
- `app/Dtos/Family/VirtualUserData.php`
- `app/Policies/FamilyPolicy.php`
- `database/migrations/xxxx_add_code_expires_at_to_families_table.php`
- `database/migrations/xxxx_create_virtual_users_table.php`
- `routes/web.php`
- `resources/js/Pages/MyPage/FamilySettings.vue`
- `resources/js/Pages/MyPage/FamilyMembers.vue`
- `resources/js/Pages/MyPage/FamilySwitch.vue`
- `resources/js/Components/Family/FamilySettingsForm.vue`
- `resources/js/Components/Family/MemberListItem.vue`
- `resources/js/Components/Family/VirtualUserListItem.vue`
- `resources/js/Components/Family/VirtualUserDialog.vue`
- `resources/js/Components/Family/FamilyCard.vue`

## 依存関係

- `files` テーブル（ポリモーフィックによる仮想ユーザーアバター管理）
- `family_user` ピボットテーブル（ロール管理）
- `users.settings` JSON カラム（`activeFamilyId` 追加）

## 既知の制約

- 家族切り替え後の Pusher チャネル再接続は今回スコープ外

## 参考資料

- `docs/working/20260401_family_settings/design.md`

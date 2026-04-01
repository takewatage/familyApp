# 設計書 - ユーザー個別設定編集機能

## アーキテクチャ

### 対象コンポーネント

```
[ブラウザ]
  └─ Pages/MyPage/index.vue
       └─ Components/MyPage/UserSettingsForm.vue
            └─ Inertia Form（useInertiaForm）
                 └─ POST /mypage/settings
                      └─ MyPageController@updateSettings
                           └─ User モデル（settings JSON カラム）
```

### 影響範囲

- `users` テーブル: `settings` カラム追加（DBマイグレーション必要）
- `MyPageController`: `updateSettings` メソッド追加
- マイページ画面（`Pages/MyPage/index.vue`）: 設定セクション追加
- `dto.generated.d.ts`: 新規DTO追加により自動更新

---

## 実装方針

### 概要

1. `users` テーブルに JSON カラム `settings` を追加し、Eloquent の `casts` で配列として扱う
2. バックエンドで `UserSettingsData` DTO を定義し、`#[TypeScript]` でフロントエンド型を自動生成
3. フロントエンドでは既存の `MyPage` ページに `UserSettingsForm` コンポーネントを追加
4. 設定保存は `POST /mypage/settings` でInertia フォームを使って送信

### 詳細

1. **DBマイグレーション**: `users` テーブルに `settings JSON nullable` カラムを追加
2. **User モデル**: `$casts = ['settings' => 'array']` を追加
3. **DTO定義**: `UserSettingsData`（表示用）と `UpdateSettingsRequestData`（更新用）を作成
4. **コントローラー**: `MyPageController` に `updateSettings` メソッドを追加。`settings` を JSON でマージ保存
5. **フロント**: `UserSettingsForm.vue` コンポーネントを作成し、`Pages/MyPage/index.vue` に組み込む

---

## データ構造

### DBカラム設計

`users` テーブルに以下カラムを追加:

| カラム名    | 型           | 制約     | 説明                        |
|-----------|-------------|--------|-----------------------------|
| `settings` | json        | nullable | ユーザー個別設定（JSON）       |

### JSON スキーマ（`settings` カラムの値）

```json
{
  "theme": "system"
}
```

将来的な設定項目追加時もこの JSON に追記するだけでスキーマ変更不要。

### PHP DTO

```php
// app/Dtos/MyPage/UserSettingsData.php
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class UserSettingsData extends Data
{
    public function __construct(
        public readonly string $theme,
    ) {}

    public static function fromArray(array $settings): self
    {
        return new self(
            theme: $settings['theme'] ?? 'system',
        );
    }
}
```

```php
// app/Dtos/MyPage/UpdateSettingsRequestData.php
#[MapInputName(CamelCaseMapper::class)]
class UpdateSettingsRequestData extends Data
{
    public function __construct(
        #[In(['light', 'dark', 'system'])]
        public readonly string $theme,
    ) {}
}
```

### 自動生成後の TypeScript 型（dto.generated.d.ts）

```typescript
// php artisan typescript:transform で自動生成される
export type UserSettingsData = {
    theme: string;
};
```

---

## API設計（Inertia）

| エンドポイント        | メソッド | コントローラー                       | 説明        |
|--------------------|---------|--------------------------------------|------------|
| `/mypage/settings` | POST    | `MyPageController@updateSettings`   | 設定を保存  |

### マイページ表示（既存）の Props 拡張

```php
// MyPageController@show に追加
Inertia::render('MyPage/index', [
    'user' => UserData::from($user),
    'settings' => UserSettingsData::fromArray($user->settings ?? []),  // 追加
]);
```

### リクエスト DTO

```php
#[MapInputName(CamelCaseMapper::class)]
class UpdateSettingsRequestData extends Data
{
    public function __construct(
        #[In(['light', 'dark', 'system'])]
        public readonly string $theme,
    ) {}
}
```

### コントローラー実装イメージ

```php
public function updateSettings(UpdateSettingsRequestData $data): RedirectResponse
{
    $user = Auth::user();
    $user->settings = [
        'theme' => $data->theme,
    ];
    $user->save();

    return back()->with('success', '設定を保存しました');
}
```

---

## DB設計（マイグレーション）

### テーブル定義の変更

`users` テーブルに `settings` カラムを追加:

| カラム名    | 型    | 制約     | 説明                  |
|-----------|------|--------|-----------------------|
| `settings` | json | nullable | ユーザー個別設定（JSON） |

### マイグレーション

```php
// database/migrations/2026_03_25_add_settings_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->json('settings')->nullable()->after('birthday');
});
```

### User モデルの変更

```php
// app/Models/User.php
protected $casts = [
    // ... 既存
    'settings' => 'array',
];
```

---

## UI設計（Vue / Vuetify）

### 画面構成

マイページ（`Pages/MyPage/index.vue`）に既存のプロフィール編集セクションの下に「設定」セクションを追加する。

### コンポーネント構成

```
Pages/MyPage/index.vue
  ├─ Components/MyPage/EditProfileForm.vue    （既存）
  └─ Components/MyPage/UserSettingsForm.vue   （新規）
```

### UserSettingsForm.vue イメージ

```vue
<template>
  <v-card class="mt-4">
    <v-card-title>設定</v-card-title>
    <v-card-text>
      <!-- テーマ -->
      <v-select
        v-model="form.theme"
        label="テーマ"
        :items="themeItems"
      />
    </v-card-text>
    <v-card-actions>
      <v-spacer />
      <v-btn
        color="primary"
        :loading="form.processing"
        @click="submit"
      >
        設定を保存
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import type { UserSettingsData } from '@/Types/dto.generated'
import { useInertiaForm } from '@/Composables/Common/useInertiaForm'
import { useSnackbar } from '@/Composables/Common/useSnackbar'

const props = defineProps<{ settings: UserSettingsData }>()
const { showSuccess, showError } = useSnackbar()

const form = useInertiaForm({ ...props.settings })

const themeItems = [
  { title: 'システム設定に合わせる', value: 'system' },
  { title: 'ライト', value: 'light' },
  { title: 'ダーク', value: 'dark' },
]

const submit = () => {
  form.post(route('mypage.settings.update'), {
    onSuccess: () => showSuccess('設定を保存しました'),
    onError: () => showError('設定の保存に失敗しました'),
  })
}
</script>
```

---

## 設計上の決定事項

| 決定事項                              | 理由                                                                 | 代替案                               |
|-------------------------------------|----------------------------------------------------------------------|-------------------------------------|
| `settings` を JSON カラム1つで管理    | 設定項目の追加時にマイグレーション不要で拡張しやすい                      | 設定項目ごとにカラムを追加（変更コスト大） |
| デフォルト値を DTO の `fromArray` で管理 | カラムが NULL の場合も安全にデフォルト値を返せる                         | DB デフォルト値に依存（NULL を返す可能性） |
| Inertia フォームで保存                | 既存の `/mypage` POST と同じパターンで一貫性がある                       | Axios で API 通信（不要な複雑化）      |
| テーマの即時反映は対象外              | テーマ適用はグローバルな状態管理が必要で別タスクとして切り出した方がよい    | 保存時に即時 Vuetify テーマを切り替える |

## 未解決事項

- [ ] テーマ設定（`theme`）の実際の Vuetify テーマへの反映方法（別タスク）
- [ ] `notifyTaskUpdate` の通知実装（プッシュ通知は現在対象外）

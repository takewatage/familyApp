# テーマカラー切り替え 設計書

## 概要

マイページの「設定」からライト/ダーク/システムの切り替えと、プライマリカラーのプリセット選択ができる機能を実装する。

---

## 1. 現状の課題

| 課題 | 詳細 |
|-----|------|
| **ライト/ダーク切り替えが未適用** | `users.settings.theme` に保存はされるが、フロントエンドでVuetifyのテーマに反映する処理が存在しない |
| **テーマカラー変更機能がない** | Vuetifyに `pinkTheme` 1つのみ定義。プライマリカラーを変更する手段がない |
| **ページをまたいで設定を参照できない** | ユーザー設定がマイページのプロパティにしか含まれておらず、他ページで参照できない |

---

## 2. 機能要件

- テーマ（ライト/ダーク/システム設定に合わせる）を切り替えられる ← **既存機能の修正**
- プリセットカラーからプライマリカラーを選択できる ← **新規**
- 設定はサーバーに保存され、次回ログイン時にも反映される

---

## 3. 設計方針

### 3.1 テーマの適用場所

全ページでテーマを反映する必要があるため、**Inertia の `HandleInertiaRequests` ミドルウェアで `userSettings` を共有プロパティとして全ページに渡す**。

```
HandleInertiaRequests::share()
  └─ userSettings: { theme, themeColor }
       ↓（全ページの $page.props に含まれる）
DokLayout.vue（watch で変更検知）
  └─ useAppTheme.ts（Vuetify テーマに適用）
```

### 3.2 カラープリセット

6色のプリセットを用意し、`themeColor` の値として保存する。

| 名前        | 値         | primary カラー |
|-----------|-----------|--------------|
| ピンク（デフォルト）| `pink`    | `#ff45ce`    |
| ブルー       | `blue`    | `#2196F3`    |
| パープル     | `purple`  | `#9C27B0`    |
| グリーン     | `green`   | `#4CAF50`    |
| オレンジ     | `orange`  | `#FF9800`    |
| ティール     | `teal`    | `#009688`    |

### 3.3 Vuetify テーマ構造

現在の `pinkTheme` 1つから、ライト/ダーク 2つの基本テーマに変更する。  
プライマリカラーは `useAppTheme.ts` で動的に書き換える。

```typescript
// vuetify.ts
const lightTheme = { dark: false, colors: { primary: '#ff45ce', ... } }
const darkTheme  = { dark: true,  colors: { primary: '#ff45ce', ... } }
```

```typescript
// useAppTheme.ts
theme.global.name.value = 'lightTheme' | 'darkTheme'
theme.themes.value['lightTheme'].colors.primary = '#2196F3'
theme.themes.value['darkTheme'].colors.primary  = '#2196F3'
```

### 3.4 `users.settings` JSONスキーマ

| キー          | 型                                               | デフォルト   |
|-------------|--------------------------------------------------|----------|
| `theme`      | `'light' \| 'dark' \| 'system'`                 | `'system'` |
| `themeColor` | `'pink' \| 'blue' \| 'purple' \| 'green' \| 'orange' \| 'teal'` | `'pink'` |

---

## 4. 実装ファイル一覧

### バックエンド

| ファイル | 変更内容 |
|--------|--------|
| `app/Http/Middleware/HandleInertiaRequests.php` | **新規作成**。`share()` で `userSettings` を全ページに共有 |
| `app/Dtos/MyPage/UserSettingsResult.php` | `theme_color` フィールドを追加 |
| `app/Dtos/MyPage/UpdateSettingsRequest.php` | `theme_color` バリデーション追加 |
| `app/Http/Controllers/MyPageController.php` | `updateSettings()` で `theme_color` を保存 |
| `bootstrap/app.php` | `HandleInertiaRequests` ミドルウェアを web グループに登録 |

### フロントエンド

| ファイル | 変更内容 |
|--------|--------|
| `resources/js/Plugins/vuetifly.ts` | `lightTheme`・`darkTheme` の2テーマ定義に変更 |
| `resources/js/Composables/Common/useAppTheme.ts` | **新規作成**。テーマ名とプライマリカラーを Vuetify に適用する composable |
| `resources/js/Layouts/DokLayout.vue` | `useAppTheme` を呼び出し、設定変更を watch してテーマ反映 |
| `resources/js/Components/MyPage/UserSettingsForm.vue` | カラープリセット選択UIを追加 |
| `resources/js/Types/dto.generated.d.ts` | `sail artisan typescript:transform` で自動再生成 |

---

## 5. 各ファイルの詳細設計

### `HandleInertiaRequests.php`

```php
public function share(Request $request): array
{
    $settings = $request->user()?->settings ?? [];
    return array_merge(parent::share($request), [
        'userSettings' => [
            'theme' => $settings['theme'] ?? 'system',
            'theme_color' => $settings['theme_color'] ?? 'pink',
        ],
    ]);
}
```

### `UserSettingsResult.php`

```php
#[TypeScript]
class UserSettingsResult extends Data
{
    public function __construct(
        #[In(['light', 'dark', 'system'])]
        public readonly string $theme,
        #[In(['pink', 'blue', 'purple', 'green', 'orange', 'teal'])]
        public readonly string $theme_color,
    ) {}

    public static function fromArray(array $settings): self
    {
        return new self(
            theme: $settings['theme'] ?? 'system',
            theme_color: $settings['theme_color'] ?? 'pink',
        );
    }
}
```

### `useAppTheme.ts`

```typescript
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useTheme } from 'vuetify'

export const THEME_COLORS: Record<string, string> = {
    pink:   '#ff45ce',
    blue:   '#2196F3',
    purple: '#9C27B0',
    green:  '#4CAF50',
    orange: '#FF9800',
    teal:   '#009688',
}

export function useAppTheme() {
    const theme = useTheme()
    const page = usePage()

    function applyTheme(settings: { theme: string; themeColor: string }) {
        // ライト/ダーク/システム
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        const isDark =
            settings.theme === 'dark' ||
            (settings.theme === 'system' && prefersDark)
        theme.global.name.value = isDark ? 'darkTheme' : 'lightTheme'

        // プライマリカラー
        const primary = THEME_COLORS[settings.themeColor] ?? THEME_COLORS.pink
        theme.themes.value['lightTheme'].colors.primary = primary
        theme.themes.value['darkTheme'].colors.primary  = primary
    }

    // 設定変更を watch（マイページで保存したときに即時反映）
    watch(
        () => (page.props as any).userSettings,
        (settings) => { if (settings) applyTheme(settings) },
        { immediate: true, deep: true },
    )

    // システムテーマ変更を監視
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const settings = (page.props as any).userSettings
        if (settings) applyTheme(settings)
    })
}
```

### `UserSettingsForm.vue`（UI 追加部分）

カラーピッカーはプリセットカラーを円形チップで表示し、選択中は枠線を付ける。

```
┌────────────────────────────┐
│ テーマ                      │
│ [システム設定に合わせる  ▼]  │
│                            │
│ テーマカラー                 │
│ ● ● ● ● ● ●              │
│  ↑選択中は枠線              │
│                            │
│              [設定を保存]   │
└────────────────────────────┘
```

---

## 6. 実装順序

1. `HandleInertiaRequests` ミドルウェア作成 + 登録
2. `UserSettingsResult` に `theme_color` 追加
3. `UpdateSettingsRequest` に `theme_color` バリデーション追加
4. `MyPageController::updateSettings()` で `theme_color` 保存
5. `sail artisan typescript:transform` で型再生成
6. `vuetifly.ts` を `lightTheme`/`darkTheme` 2テーマに変更
7. `useAppTheme.ts` 作成
8. `DokLayout.vue` で `useAppTheme()` 呼び出し
9. `UserSettingsForm.vue` にカラープリセットUI追加

---

## 7. 未決定事項

| 項目 | 状況 | 内容 |
|-----|------|------|
| secondary カラーの扱い | 未定 | プライマリと連動させるかどうか |
| カスタムカラー入力 | スコープ外 | カラーコードを直接入力できる機能（今回はプリセットのみ） |

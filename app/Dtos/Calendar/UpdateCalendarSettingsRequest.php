<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カレンダー設定の更新（送った項目だけを更新する）
 */
#[TypeScript]
class UpdateCalendarSettingsRequest extends Data
{
    public function __construct(
        /** 誕生日の予定の色（#rrggbb。null で既定の色に戻す） */
        #[Optional]
        public ?string $birthday_color = null,
        /** 祝日を表示するか（ログイン中のユーザーだけに反映） */
        #[Optional]
        public ?bool $show_holidays = null,
    ) {}

    public static function rules(): array
    {
        return [
            'birthday_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_holidays' => ['sometimes', 'boolean'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'birthday_color' => '誕生日のカラー',
            'show_holidays' => '祝日を表示',
        ];
    }
}

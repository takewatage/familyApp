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
    ) {}

    public static function rules(): array
    {
        return [
            'birthday_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'birthday_color' => '誕生日のカラー',
        ];
    }
}

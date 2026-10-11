<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カレンダー設定。設定を追加するときはここに項目を足す
 *
 * - 家族で共有する項目: families.settings.calendar
 * - ユーザーごとの項目: users.settings.calendar
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CalendarSettingsResult extends Data
{
    public function __construct(
        /** 誕生日の予定の色（#rrggbb。未設定なら画面の既定色） */
        public ?string $birthday_color = null,
        /** 祝日を表示するか（ユーザーごと。未設定なら表示する） */
        public bool $show_holidays = true,
    ) {}
}

<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 家族のカレンダー設定（families.settings.calendar）。設定を追加するときはここに項目を足す
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CalendarSettingsResult extends Data
{
    public function __construct(
        /** 誕生日の予定の色（#rrggbb。未設定なら画面の既定色） */
        public ?string $birthday_color = null,
    ) {}
}

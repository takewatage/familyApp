<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 期間内の予定の取得条件
 */
#[TypeScript]
class CalendarEventsRequest extends Data
{
    /** 1 回に取得できる最大日数（表示中の月と前後の月を含められる長さ） */
    public const MAX_DAYS = 100;

    public function __construct(
        public string $from,
        public string $to,
    ) {}

    public static function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:from',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $from = request()->input('from');

                    if ($from && strtotime($value) - strtotime($from) > self::MAX_DAYS * 86400) {
                        $fail('取得できる期間は '.self::MAX_DAYS.' 日までです。');
                    }
                },
            ],
        ];
    }
}

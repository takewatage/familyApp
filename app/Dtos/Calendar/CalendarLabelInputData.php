<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * ラベル一覧の一括更新で送る 1 件
 */
#[TypeScript]
class CalendarLabelInputData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $color,
    ) {}
}

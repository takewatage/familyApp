<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CalendarLabelResult extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        /** #rrggbb */
        public string $color,
    ) {}
}

<?php

namespace App\Dtos\Calendar;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CalendarPageResult extends Data
{
    public function __construct(
        /** @var CalendarParticipantResult[] */
        public array $participants,
    ) {}
}

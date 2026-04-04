<?php

namespace App\Dtos\Family;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilySwitchResult extends Data
{
    public function __construct(
        /** @var DataCollection<int, FamilyForSwitchData> */
        public DataCollection $families,
        public ?string $current_family_id,
    ) {}
}

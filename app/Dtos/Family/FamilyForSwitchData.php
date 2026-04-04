<?php

namespace App\Dtos\Family;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilyForSwitchData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var string[] */
        public array $member_names,
    ) {}
}

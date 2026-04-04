<?php

namespace App\Dtos\Family;

use App\Dtos\Model\FamilyData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilySettingsResult extends Data
{
    public function __construct(
        public FamilyData $family,
        public bool $is_owner,
    ) {}
}

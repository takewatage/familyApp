<?php

namespace App\Dtos\Auth;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class RegisterPageResult extends Data
{
    public function __construct(
        public readonly string $family_name,
    ) {}
}

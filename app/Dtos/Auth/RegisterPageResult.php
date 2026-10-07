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
        /** 招待先の家族名（新規登録は招待経由のみ） */
        public readonly string $family_name,
        public readonly bool $google_enabled = false,
    ) {}
}

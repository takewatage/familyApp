<?php

namespace App\Dtos\Auth;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class LoginPageResult extends Data
{
    public function __construct(
        public readonly bool $can_reset_password,
        public readonly bool $google_enabled,
        public readonly ?string $status = null,
    ) {}
}

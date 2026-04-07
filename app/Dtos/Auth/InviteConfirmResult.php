<?php

namespace App\Dtos\Auth;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class InviteConfirmResult extends Data
{
    public function __construct(
        public readonly ?string $family_name,
        public readonly int $member_count,
        public readonly string $code,
        public readonly bool $already_joined,
        public readonly bool $is_full,
        public readonly ?string $error,
        public readonly string $invite_role = 'guest',
    ) {}
}

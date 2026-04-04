<?php

namespace App\Dtos\Family;

use App\Dtos\Model\FileData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilyMemberData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $role,
        public FileData|Optional|null $avatar,
    ) {}
}

<?php

namespace App\Dtos\Model;

use App\Dtos\Model\FileData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class VirtualUserData extends Data
{
    public function __construct(
        public string $id,
        public string $family_id,
        public string $name,
        public ?string $created_at,
        public ?string $updated_at,
        // accessor
        public FileData|Optional|null $avatar,
    ) {}
}

<?php

namespace App\Dtos\Model;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FileData extends Data
{
    public function __construct(
        public string $id,
        public string $fileable_type,
        public string $fileable_id,
        public string $collection,
        public string $path,
        public string $url,
        public string $name,
        public string $mime_type,
        public string $sort,
        public ?string $created_at,
        public ?string $updated_at
    ) {}
}

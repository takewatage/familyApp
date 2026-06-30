<?php

namespace App\Dtos\Model;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class ShopData extends Data
{
    public function __construct(
        public string $id,
        public string $family_id,
        public string $name,
        public ?string $default_category_id,
        public int $usage_count,
    ) {}
}

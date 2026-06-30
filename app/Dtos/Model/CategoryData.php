<?php

namespace App\Dtos\Model;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CategoryData extends Data
{
    public function __construct(
        public string $id,
        public ?string $family_id,
        public ?string $parent_id,
        public string $name,
        public ?string $icon,
        public string $color,
        public int $sort_order,
        public bool $is_system,
        public bool $is_active,
    ) {}
}

<?php

namespace App\Dtos\Dok;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class TaskCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?int   $sort,
    )
    {
    }
}

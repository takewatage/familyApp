<?php

namespace App\Dtos\Dok;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
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

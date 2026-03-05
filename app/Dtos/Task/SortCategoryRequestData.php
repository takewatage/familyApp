<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SortCategoryRequestData extends Data
{
    public function __construct(
        public string $id,
        public int    $sort,
    )
    {
    }
}

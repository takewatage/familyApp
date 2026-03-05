<?php

namespace App\Dtos\Dok;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskCategoryResult extends Data
{
    public function __construct(
        public bool             $success,
        public TaskCategoryData $category,
    ) {
    }
}

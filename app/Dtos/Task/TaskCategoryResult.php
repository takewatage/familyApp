<?php

namespace App\Dtos\Task;

use App\Dtos\Model\TaskCategoryData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskCategoryResult extends Data
{
    public function __construct(
        public bool             $success,
        public TaskCategoryData $category,
    )
    {
    }
}

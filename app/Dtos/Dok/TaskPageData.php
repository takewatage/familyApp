<?php

namespace App\Dtos\Dok;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TaskPageData extends Data
{
    public function __construct(
        /**
         * @var TaskCategoryData[]
         * */
        public array  $categories,
        /** @var TaskData[] */
        public array  $tasks,

        public string $familyId,
    )
    {
    }
}

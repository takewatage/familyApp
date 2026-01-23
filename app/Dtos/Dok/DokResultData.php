<?php

namespace App\Dtos\Dok;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class DokResultData extends Data
{
    public function __construct(
        /**
         * @var TaskCategoryData[]
         * */
        public array $categories,
        /** @var TaskData[] */
        public array $tasks,
    )
    {
    }
}

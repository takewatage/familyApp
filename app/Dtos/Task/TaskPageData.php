<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
#[MapOutputName(CamelCaseMapper::class)]
class TaskPageData extends Data
{
    public function __construct(
        /**
         * @var TaskCategoryData[]
         * */
        public array  $categories,
        /** @var TaskData[] */
        public array  $tasks,

        public string $family_id,
    )
    {
    }
}

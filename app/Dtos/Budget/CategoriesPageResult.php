<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\CategoryData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class CategoriesPageResult extends Data
{
    public function __construct(
        /** @var CategoryData[] */
        public array $categories,
    ) {}
}

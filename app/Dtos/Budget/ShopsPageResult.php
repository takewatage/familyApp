<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\CategoryData;
use App\Dtos\Model\ShopData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class ShopsPageResult extends Data
{
    public function __construct(
        /** @var ShopData[] */
        public array $shops,
        /** @var CategoryData[] */
        public array $categories,
    ) {}
}

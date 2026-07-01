<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\PaymentMethodData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class PaymentMethodsPageResult extends Data
{
    public function __construct(
        /** @var PaymentMethodData[] */
        public array $payment_methods,
    ) {}
}

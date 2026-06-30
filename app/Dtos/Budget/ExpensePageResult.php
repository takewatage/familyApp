<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\CategoryData;
use App\Dtos\Model\ExpenseData;
use App\Dtos\Model\PaymentMethodData;
use App\Dtos\Model\ShopData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class ExpensePageResult extends Data
{
    public function __construct(
        /** @var ExpenseData[] */
        public array $expenses,
        /** @var CategoryData[] */
        public array $categories,
        /** @var PaymentMethodData[] */
        public array $payment_methods,
        /** @var ShopData[] */
        public array $shops,
        /** @var MemberOptionData[] */
        public array $member_options,
        public string $year_month,
        public string $total_amount,
    ) {}
}

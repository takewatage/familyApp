<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\CategoryData;
use App\Dtos\Model\PaymentMethodData;
use App\Dtos\Model\RecurringExpenseData;
use App\Dtos\Model\ShopData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class RecurringExpensesPageResult extends Data
{
    public function __construct(
        /** @var RecurringExpenseData[] */
        public array $recurring_expenses,
        /** @var CategoryData[] */
        public array $categories,
        /** @var PaymentMethodData[] */
        public array $payment_methods,
        /** @var ShopData[] */
        public array $shops,
        /** @var MemberOptionData[] */
        public array $member_options,
    ) {}
}

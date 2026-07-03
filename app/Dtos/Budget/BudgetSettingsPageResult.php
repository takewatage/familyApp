<?php

namespace App\Dtos\Budget;

use App\Dtos\Model\BudgetAlertData;
use App\Dtos\Model\BudgetCategoryData;
use App\Dtos\Model\CategoryData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予算設定画面（`Budget/BudgetSettings`）の表示データ。
 * 月収入・貯金目標は当月予算（budgets）の値、未設定月は 0 を返す。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class BudgetSettingsPageResult extends Data
{
    public function __construct(
        public string $year_month,
        public string $total_income,
        public string $saving_target,
        /** @var BudgetCategoryData[] */
        public array $category_budgets,
        /** @var BudgetAlertData[] */
        public array $alerts,
        /** @var CategoryData[] */
        public array $categories,
    ) {}
}

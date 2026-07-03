<?php

namespace App\Dtos\Budget;

use App\Services\CurrentFamilyService;
use App\Support\BudgetScopeRules;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カテゴリー別予算の一括保存（F-10）。当月予算（budgets）配下の budget_categories を upsert する。
 * カテゴリーは「システム既定 or 家族の有効なもの」に限定する。
 */
#[TypeScript]
class StoreBudgetCategoriesRequest extends Data
{
    public function __construct(
        public string $year_month,
        /** @var BudgetCategoryInputData[] */
        public array $category_budgets,
    ) {}

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'year_month' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'category_budgets' => ['present', 'array'],
            // distinct: 同一カテゴリーの重複行を弾く（無いと後勝ちで黙って上書きされる）
            'category_budgets.*.category_id' => ['required', 'string', 'distinct', BudgetScopeRules::activeCategory($familyId)],
            'category_budgets.*.amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'year_month' => '対象年月',
            'category_budgets.*.category_id' => 'カテゴリー',
            'category_budgets.*.amount' => '予算額',
        ];
    }
}

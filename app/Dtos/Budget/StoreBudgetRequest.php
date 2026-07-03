<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 月収入・貯金目標の保存（F-9）。family_id + year_month で upsert する。
 */
#[TypeScript]
class StoreBudgetRequest extends Data
{
    public function __construct(
        public string $year_month,
        public string $total_income,
        public string $saving_target,
    ) {}

    public static function rules(): array
    {
        return [
            // 月は 01-12 のみ許可（範囲外は Carbon が桁上がりし集計月とずれるため弾く）
            'year_month' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'total_income' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'saving_target' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'year_month' => '対象年月',
            'total_income' => '月収入',
            'saving_target' => '貯金目標',
        ];
    }
}

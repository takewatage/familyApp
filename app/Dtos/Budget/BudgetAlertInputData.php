<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予算アラート 1 件分の入力。category_id が null のものは家族全体アラート。
 * StoreBudgetAlertsRequest の配列要素として使う。
 */
#[TypeScript]
class BudgetAlertInputData extends Data
{
    public function __construct(
        public int $threshold_percent,
        public bool $is_enabled,
        #[Optional]
        public ?string $category_id = null,
    ) {}
}

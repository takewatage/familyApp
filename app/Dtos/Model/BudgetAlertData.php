<?php

namespace App\Dtos\Model;

use App\Models\BudgetAlert;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予算アラート設定の表示用 DTO。category_id が null のものは家族全体アラート。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class BudgetAlertData extends Data
{
    public function __construct(
        public string $id,
        public ?string $category_id,
        public int $threshold_percent,
        public bool $is_enabled,
    ) {}

    public static function fromModel(BudgetAlert $alert): self
    {
        return new self(
            id: $alert->id,
            category_id: $alert->category_id,
            threshold_percent: $alert->threshold_percent,
            is_enabled: $alert->is_enabled,
        );
    }
}

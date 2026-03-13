<?php

namespace App\Dtos\Model;

use App\Dtos\Model\FamilyData;
use App\Dtos\Model\TaskCategoryData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class TaskData extends Data
{
    public function __construct(
        public string $id,
        public string $family_id,
        public string $category_id,
        public string $content,
        public ?string $color,
        public ?string $memo,
        public bool $is_completed,
        public ?string $completed_at,
        public int $sort,
        public ?string $created_at,
        public ?string $updated_at,
        // relation: BelongsTo
        public FamilyData|Optional $family,
        // relation: BelongsTo
        public TaskCategoryData|Optional $category
    ) {}
}

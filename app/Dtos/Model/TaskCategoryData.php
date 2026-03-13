<?php

namespace App\Dtos\Model;

use App\Dtos\Model\FamilyData;
use App\Dtos\Model\TaskData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class TaskCategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $family_id,
        public string $name,
        public int $sort,
        public ?string $created_at,
        public ?string $updated_at,
        // relation: BelongsTo
        public FamilyData|Optional $family,
        // relation: HasMany
        /** @var DataCollection<int, TaskData> */
        public DataCollection|Optional $tasks
    ) {}
}

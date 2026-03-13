<?php

namespace App\Dtos\Model;

use App\Dtos\Model\TaskCategoryData;
use App\Dtos\Model\TaskData;
use App\Dtos\Model\UserData;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class FamilyData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public string $owner_id,
        public int $max_members,
        public ?array $settings,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at,
        // relation: BelongsTo
        public UserData|Optional $owner,
        // relation: HasMany
        /** @var DataCollection<int, TaskCategoryData> */
        public DataCollection|Optional $categories,
        // relation: HasMany
        /** @var DataCollection<int, TaskData> */
        public DataCollection|Optional $tasks
    ) {}
}

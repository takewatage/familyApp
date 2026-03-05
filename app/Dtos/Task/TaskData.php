<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class TaskData extends Data
{
    public function __construct(
        public string  $id,
        public string  $familyId,
        public string  $categoryId,
        public string  $content,
        #[Optional]
        public ?string $color,
        #[Optional]
        public ?string $memo,
        public bool    $isCompleted,
        #[Optional]
        public ?string $completedAt,
        public int     $sort,
    )
    {
    }
}

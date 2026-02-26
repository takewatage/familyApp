<?php

namespace App\Dtos\Dok;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class TaskData extends Data
{
    public function __construct(
        public string  $id,
        public string  $content,
        public string  $categoryId,
        public ?string $color,
        public ?string $memo,
        public bool    $isCompleted,
        public ?int    $sort,
    )
    {
    }
}

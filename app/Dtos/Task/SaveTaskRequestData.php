<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(CamelCaseMapper::class)]
class SaveTaskRequestData extends Data
{
    public function __construct(
        #[Optional]
        public ?string $id,
        public string  $family_id,
        public string  $category_id,
        public string  $content,
        #[Optional]
        public ?string $color,
        #[Optional]
        public ?string $memo,
    )
    {
    }

    public static function rules(): array
    {
        return [
            'id' => 'sometimes|exists:tasks,id',
            'content' => 'required|string|max:255',
            'category_id' => 'required|exists:task_categories,id',
            'color' => 'required|string|max:50',
            'memo' => 'nullable|string|max:1000',
        ];
    }

    public static function attributes(): array
    {
        return [
            'category_id' => 'カテゴリー',
        ];
    }

}

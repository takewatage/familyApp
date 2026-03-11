<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(CamelCaseMapper::class)]
class DeleteCompletedTasksRequest extends Data
{
    public function __construct(
        public string $category_id,
    )
    {
    }

    public static function rules(): array
    {
        return [
            'category_id' => 'required|exists:task_categories,id',
        ];
    }
}

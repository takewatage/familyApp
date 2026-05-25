<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
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

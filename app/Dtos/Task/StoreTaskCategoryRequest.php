<?php

namespace App\Dtos\Task;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StoreTaskCategoryRequest extends Data
{
    public function __construct(
        public string $name,
    )
    {
    }

    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}

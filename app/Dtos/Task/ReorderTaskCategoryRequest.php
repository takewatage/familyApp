<?php

namespace App\Dtos\Task;

use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ReorderTaskCategoryRequest extends Data
{
    public function __construct(
        /**
         * @var SortCategoryRequestData[]
         * */
        public array $categories,
    )
    {
    }

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'string', Rule::exists('task_categories', 'id')->where('family_id', $familyId)],
            'categories.*.sort' => ['required', 'integer'],
        ];
    }
}

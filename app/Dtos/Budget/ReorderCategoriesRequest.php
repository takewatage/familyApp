<?php

namespace App\Dtos\Budget;

use App\Dtos\Common\SortItemData;
use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ReorderCategoriesRequest extends Data
{
    public function __construct(
        /** @var SortItemData[] */
        public array $categories,
    ) {}

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'categories' => ['required', 'array'],
            // 並び替え対象は家族自身のカテゴリーのみ（システム既定 family_id=null は対象外）
            'categories.*.id' => ['required', 'string', Rule::exists('categories', 'id')->where('family_id', $familyId)],
            'categories.*.sort' => ['required', 'integer', 'min:0'],
        ];
    }
}

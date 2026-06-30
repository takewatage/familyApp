<?php

namespace App\Dtos\Budget;

use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StoreCategoryRequest extends Data
{
    public function __construct(
        public string $name,
        public string $color,
        #[Optional]
        public ?string $icon = null,
        #[Optional]
        public ?string $parent_id = null,
    ) {}

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'name' => ['required', 'string', 'max:50'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            // 親は「家族自身の・有効な・最上位」カテゴリーのみ許可する。
            // - family_id=$familyId に限定: システム既定（family_id=null）は読み取り専用で子を表示できず孤児化するため除外
            // - is_active=true に限定: 論理削除済みの親は一覧に出ず、その子が画面から消える孤児化を防ぐ
            // - parent_id IS NULL に限定: 親子 2 階層制限
            'parent_id' => [
                'nullable',
                'string',
                Rule::exists('categories', 'id')->where(function ($query) use ($familyId) {
                    $query->where('family_id', $familyId)
                        ->where('is_active', true)
                        ->whereNull('parent_id');
                }),
            ],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => 'カテゴリー名',
            'color' => '色',
            'icon' => 'アイコン',
            'parent_id' => '親カテゴリー',
        ];
    }
}

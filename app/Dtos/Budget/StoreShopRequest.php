<?php

namespace App\Dtos\Budget;

use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StoreShopRequest extends Data
{
    public function __construct(
        public string $name,
        #[Optional]
        public ?string $default_category_id = null,
    ) {}

    public static function rules(): array
    {
        return self::shopRules();
    }

    /**
     * 店舗バリデーションルール。store / update 共通で family_id を一度だけ解決する。
     * 更新時は店舗名一意制約から自分自身を除外するため $ignoreShopId を渡す。
     */
    protected static function shopRules(?string $ignoreShopId = null): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        // 同一家族内で店舗名は一意（DB の (family_id, name) UNIQUE と整合）
        $unique = Rule::unique('shops', 'name')->where('family_id', $familyId);

        if ($ignoreShopId !== null) {
            $unique->ignore($ignoreShopId);
        }

        return [
            'name' => ['required', 'string', 'max:100', $unique],
            // デフォルトカテゴリーは家族 or システム既定の有効なもののみ
            'default_category_id' => [
                'nullable',
                'string',
                Rule::exists('categories', 'id')->where(function ($query) use ($familyId) {
                    $query->where(function ($scope) use ($familyId) {
                        $scope->whereNull('family_id')->orWhere('family_id', $familyId);
                    })->where('is_active', true);
                }),
            ],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => '店舗名',
            'default_category_id' => 'デフォルトカテゴリー',
        ];
    }
}

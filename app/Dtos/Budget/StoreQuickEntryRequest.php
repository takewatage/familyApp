<?php

namespace App\Dtos\Budget;

use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StoreQuickEntryRequest extends Data
{
    public function __construct(
        public string $name,
        public string $category_id,
        public string $payment_method_id,
        #[Optional]
        public ?string $shop_id = null,
        #[Optional]
        public ?string $default_amount = null,
    ) {}

    /**
     * クイック入力バリデーションルール。store / update 共通。
     * カテゴリー・支払い方法は「システム既定 or 家族の有効なもの」、店舗は「家族のもの」に限定する。
     */
    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'name' => ['required', 'string', 'max:50'],
            'category_id' => [
                'required',
                'string',
                Rule::exists('categories', 'id')->where(function ($query) use ($familyId) {
                    $query->where(function ($scope) use ($familyId) {
                        $scope->whereNull('family_id')->orWhere('family_id', $familyId);
                    })->where('is_active', true);
                }),
            ],
            'payment_method_id' => [
                'required',
                'string',
                Rule::exists('payment_methods', 'id')->where(function ($query) use ($familyId) {
                    $query->where(function ($scope) use ($familyId) {
                        $scope->whereNull('family_id')->orWhere('family_id', $familyId);
                    })->where('is_active', true);
                }),
            ],
            'shop_id' => [
                'nullable',
                'string',
                Rule::exists('shops', 'id')->where('family_id', $familyId),
            ],
            'default_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => 'クイック入力名',
            'category_id' => 'カテゴリー',
            'payment_method_id' => '支払い方法',
            'shop_id' => '店舗',
            'default_amount' => 'デフォルト金額',
        ];
    }
}

<?php

namespace App\Dtos\Budget;

use App\Models\User;
use App\Models\VirtualUser;
use App\Services\CurrentFamilyService;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StoreRecurringExpenseRequest extends Data
{
    public function __construct(
        public string $name,
        public string $amount,
        public string $category_id,
        public string $payment_method_id,
        public int $day_of_month,
        public string $start_date,
        #[Optional]
        public ?string $end_date = null,
        #[Optional]
        public ?string $shop_id = null,
        #[Optional]
        public ?string $member_type = null,
        #[Optional]
        public ?string $member_id = null,
    ) {}

    /**
     * 繰り返し支出バリデーションルール。store / update 共通。
     * カテゴリー・支払い方法は「システム既定 or 家族の有効なもの」、店舗は「家族のもの」に限定する。
     * 担当者（member）の家族スコープは RecurringExpenseService で検証する（exists はグローバル存在のみを見るため）。
     */
    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
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
            'day_of_month' => ['required', 'integer', 'min:1', 'max:31'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'shop_id' => [
                'nullable',
                'string',
                Rule::exists('shops', 'id')->where('family_id', $familyId),
            ],
            'member_type' => ['nullable', 'string', 'in:'.User::class.','.VirtualUser::class],
            'member_id' => ['nullable', 'string', 'required_with:member_type'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => '支出名',
            'amount' => '金額',
            'category_id' => 'カテゴリー',
            'payment_method_id' => '支払い方法',
            'day_of_month' => '支払日',
            'start_date' => '開始日',
            'end_date' => '終了日',
            'shop_id' => '店舗',
        ];
    }
}

<?php

namespace App\Dtos\Budget;

use App\Models\User;
use App\Models\VirtualUser;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StoreExpenseRequest extends Data
{
    public function __construct(
        public string $amount,
        public string $category_id,
        public string $payment_method_id,
        public string $expense_date,
        #[Optional]
        public ?string $shop_id = null,
        #[Optional]
        public ?string $shop_name = null,
        #[Optional]
        public ?string $member_type = null,
        #[Optional]
        public ?string $member_id = null,
        #[Optional]
        public ?string $memo = null,
    ) {}

    public static function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['required', 'string', 'exists:categories,id'],
            'payment_method_id' => ['required', 'string', 'exists:payment_methods,id'],
            'expense_date' => ['required', 'date'],
            'shop_id' => ['nullable', 'string', 'exists:shops,id'],
            'shop_name' => ['nullable', 'string', 'max:100'],
            'member_type' => ['nullable', 'string', 'in:'.User::class.','.VirtualUser::class],
            'member_id' => ['nullable', 'string', 'required_with:member_type'],
            'memo' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'amount' => '金額',
            'category_id' => 'カテゴリー',
            'payment_method_id' => '支払い方法',
            'expense_date' => '支出日',
            'shop_name' => '店名',
            'memo' => 'メモ',
        ];
    }
}

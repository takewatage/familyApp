<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class StorePaymentMethodRequest extends Data
{
    public function __construct(
        public string $name,
        #[Optional]
        public ?string $icon = null,
    ) {}

    /**
     * 支払い方法バリデーションルール。store / update 共通（名前の一意制約はないため update でも同一）。
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:50'],
        ];
    }

    public static function attributes(): array
    {
        return [
            'name' => '支払い方法名',
            'icon' => 'アイコン',
        ];
    }
}

<?php

namespace App\Dtos\Model;

use App\Models\RecurringExpense;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 繰り返し支出（固定費）表示用 DTO。編集に必要な FK と、一覧描画用の非正規化表示フィールドを併せ持つ。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class RecurringExpenseData extends Data
{
    public function __construct(
        public string $id,
        public string $family_id,
        // 担当者（polymorphic: User / VirtualUser・任意）
        public ?string $member_type,
        public ?string $member_id,
        public ?string $member_name,
        // カテゴリー
        public string $category_id,
        public string $category_name,
        public string $category_color,
        public ?string $category_icon,
        // 支払い方法
        public string $payment_method_id,
        public string $payment_method_name,
        // 店舗（任意）
        public ?string $shop_id,
        public ?string $shop_name,
        public string $name,
        public string $amount,
        public int $day_of_month,
        public string $start_date,
        public ?string $end_date,
        public bool $is_active,
        public ?string $last_generated_date,
    ) {}

    /**
     * Eager-load 済み（category / paymentMethod / shop / member）の RecurringExpense から生成する。
     */
    public static function fromModel(RecurringExpense $recurring): self
    {
        return new self(
            id: $recurring->id,
            family_id: $recurring->family_id,
            member_type: $recurring->member_type,
            member_id: $recurring->member_id,
            member_name: $recurring->member?->name,
            category_id: $recurring->category_id,
            category_name: $recurring->category?->name ?? '',
            category_color: $recurring->category?->color ?? '#6B7280',
            category_icon: $recurring->category?->icon,
            payment_method_id: $recurring->payment_method_id,
            payment_method_name: $recurring->paymentMethod?->name ?? '',
            shop_id: $recurring->shop_id,
            shop_name: $recurring->shop?->name,
            name: $recurring->name,
            amount: (string) $recurring->amount,
            day_of_month: $recurring->day_of_month,
            start_date: $recurring->start_date?->format('Y-m-d') ?? '',
            end_date: $recurring->end_date?->format('Y-m-d'),
            is_active: $recurring->is_active,
            last_generated_date: $recurring->last_generated_date?->format('Y-m-d'),
        );
    }
}

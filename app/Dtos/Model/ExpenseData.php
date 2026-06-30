<?php

namespace App\Dtos\Model;

use App\Models\Expense;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 支出一覧表示用 DTO。編集に必要な FK と、一覧描画用の非正規化表示フィールドを併せ持つ。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class ExpenseData extends Data
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
        // 店舗（shop_id 登録済み or shop_name 手入力）
        public ?string $shop_id,
        public ?string $shop_name,
        public ?string $shop_display_name,
        public string $amount,
        public string $expense_date,
        public ?string $memo,
        public bool $is_recurring,
        public ?string $created_at,
    ) {}

    /**
     * Eager-load 済み（category / paymentMethod / shop / member）の Expense から生成する。
     */
    public static function fromModel(Expense $expense): self
    {
        return new self(
            id: $expense->id,
            family_id: $expense->family_id,
            member_type: $expense->member_type,
            member_id: $expense->member_id,
            member_name: $expense->member?->name,
            category_id: $expense->category_id,
            category_name: $expense->category?->name ?? '',
            category_color: $expense->category?->color ?? '#6B7280',
            category_icon: $expense->category?->icon,
            payment_method_id: $expense->payment_method_id,
            payment_method_name: $expense->paymentMethod?->name ?? '',
            shop_id: $expense->shop_id,
            shop_name: $expense->shop_name,
            shop_display_name: $expense->shop?->name ?? $expense->shop_name,
            amount: (string) $expense->amount,
            expense_date: $expense->expense_date?->format('Y-m-d') ?? '',
            memo: $expense->memo,
            is_recurring: $expense->is_recurring,
            created_at: $expense->created_at?->toIso8601String(),
        );
    }
}

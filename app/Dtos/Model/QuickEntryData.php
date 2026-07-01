<?php

namespace App\Dtos\Model;

use App\Models\QuickEntry;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * クイック入力表示用 DTO。支出フォームへのプリセットに必要な FK と、
 * 一覧描画用の非正規化表示フィールド（カテゴリー名/色/アイコン・支払い方法名・店名）を併せ持つ。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class QuickEntryData extends Data
{
    public function __construct(
        public string $id,
        public string $family_id,
        public string $name,
        public string $category_id,
        public string $category_name,
        public string $category_color,
        public ?string $category_icon,
        public string $payment_method_id,
        public string $payment_method_name,
        public ?string $shop_id,
        public ?string $shop_name,
        public ?string $default_amount,
        public int $sort_order,
        public int $usage_count,
    ) {}

    /**
     * Eager-load 済み（category / paymentMethod / shop）の QuickEntry から生成する。
     */
    public static function fromModel(QuickEntry $quickEntry): self
    {
        return new self(
            id: $quickEntry->id,
            family_id: $quickEntry->family_id,
            name: $quickEntry->name,
            category_id: $quickEntry->category_id,
            category_name: $quickEntry->category?->name ?? '',
            category_color: $quickEntry->category?->color ?? '#6B7280',
            category_icon: $quickEntry->category?->icon,
            payment_method_id: $quickEntry->payment_method_id,
            payment_method_name: $quickEntry->paymentMethod?->name ?? '',
            shop_id: $quickEntry->shop_id,
            shop_name: $quickEntry->shop?->name,
            default_amount: $quickEntry->default_amount !== null ? (string) $quickEntry->default_amount : null,
            sort_order: $quickEntry->sort_order,
            usage_count: $quickEntry->usage_count,
        );
    }
}

<?php

namespace App\Dtos\Budget;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 店舗更新リクエスト。店舗名の一意制約のみ「自分自身を除外」する点が StoreShopRequest と異なる。
 */
#[TypeScript]
class UpdateShopRequest extends StoreShopRequest
{
    public static function rules(): array
    {
        $shop = request()->route('shop');
        $shopId = is_object($shop) ? $shop->id : $shop;

        return self::shopRules($shopId);
    }
}

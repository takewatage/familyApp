<?php

namespace App\Dtos\Common;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 並び替え用の汎用アイテム（id + 並び順）。
 * タスクカテゴリー・家計簿カテゴリー等のドラッグ並び替えで共通利用する。
 * 単語プロパティ sort で axios 直送時の camel/snake 不整合を回避する。
 */
#[TypeScript]
class SortItemData extends Data
{
    public function __construct(
        public string $id,
        public int $sort,
    ) {}
}

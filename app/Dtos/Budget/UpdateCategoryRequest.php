<?php

namespace App\Dtos\Budget;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * カテゴリー更新リクエスト。入力・バリデーションは StoreCategoryRequest と同一のため継承する。
 * （自己参照・循環・子を持つカテゴリーの親化防止は CategoryController 側で検証する）
 */
#[TypeScript]
class UpdateCategoryRequest extends StoreCategoryRequest {}

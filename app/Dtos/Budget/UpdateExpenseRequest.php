<?php

namespace App\Dtos\Budget;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 支出更新リクエスト。入力・バリデーションは StoreExpenseRequest と同一のため継承する。
 * （構成が分岐した時点で個別の constructor / rules() を定義する）
 */
#[TypeScript]
class UpdateExpenseRequest extends StoreExpenseRequest {}

<?php

namespace App\Dtos\Budget;

use App\Services\CurrentFamilyService;
use App\Support\BudgetScopeRules;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 予算アラート設定の一括保存（F-12 設定）。家族のアラート集合を丸ごと置き換える。
 * category_id は「システム既定 or 家族の有効なもの」、または null（全体アラート）に限定する。
 */
#[TypeScript]
class StoreBudgetAlertsRequest extends Data
{
    public function __construct(
        /** @var BudgetAlertInputData[] */
        public array $alerts,
    ) {}

    public static function rules(): array
    {
        $familyId = app(CurrentFamilyService::class)->getCurrentFamilyId();

        return [
            'alerts' => ['present', 'array', self::distinctTargetsRule()],
            // is_active は問わない: カテゴリーが後から無効化されても既存アラートの再保存を妨げない
            // （ドロップダウンは有効カテゴリーのみ表示するため新規作成は有効カテゴリーに限られる）。
            'alerts.*.category_id' => ['nullable', 'string', BudgetScopeRules::familyCategory($familyId)],
            'alerts.*.threshold_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'alerts.*.is_enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * 同一ターゲット（全体=null / 同一カテゴリー）のアラートが重複していないか検証する。
     * updateOrCreate((family_id, category_id)) は重複を黙って 1 行へ畳むため、ここで弾く。
     * null（全体）は '__overall__' に写像して比較する（標準 distinct は複数 null を扱えない）。
     */
    private static function distinctTargetsRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_array($value)) {
                return;
            }

            $targets = array_map(
                fn ($alert) => (is_array($alert) ? ($alert['category_id'] ?? null) : null) ?? '__overall__',
                $value,
            );

            if (count($targets) !== count(array_unique($targets))) {
                $fail('同じ対象のアラートが重複しています。');
            }
        };
    }

    public static function attributes(): array
    {
        return [
            'alerts.*.category_id' => 'カテゴリー',
            'alerts.*.threshold_percent' => 'アラート閾値',
            'alerts.*.is_enabled' => '有効',
        ];
    }
}

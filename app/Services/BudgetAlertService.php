<?php

namespace App\Services;

use App\Dtos\Budget\BudgetCalculationResult;
use App\Models\AlertNotification;
use App\Models\BudgetAlert;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * 予算アラート判定（F-12）。消化率が閾値に達した有効アラートを検知し、
 * alert_notifications に記録する。同一アラート・同一年月では重複通知しない
 * （alert_id + year_month の UNIQUE 制約 + firstOrCreate で担保）。
 */
class BudgetAlertService
{
    private const SCALE = 2;

    public function __construct(
        private readonly BudgetCalculationService $calculationService,
    ) {}

    /**
     * 指定家族・年月のアラートを判定し、新規に発火した通知の配列を返す。
     * 既に同月で通知済みのアラートは再発火しない。
     *
     * @param  CarbonInterface|string|null  $triggeredAt  通知発生日時（省略時は現在）
     * @return AlertNotification[]
     */
    public function evaluate(string $familyId, string $yearMonth, CarbonInterface|string|null $triggeredAt = null): array
    {
        $triggeredAt = $triggeredAt ? Carbon::parse($triggeredAt) : Carbon::now();
        $result = $this->calculationService->calculate($familyId, $yearMonth);

        $alerts = BudgetAlert::where('family_id', $familyId)
            ->where('is_enabled', true)
            ->get();

        $triggered = [];

        foreach ($alerts as $alert) {
            $actualPercent = $this->actualPercentFor($alert, $result);

            // 予算未設定（分母 0）で消化率を定義できないアラートは判定対象外。
            if ($actualPercent === null) {
                continue;
            }

            // 消化率が閾値未満なら未発火。
            if (bccomp($actualPercent, (string) $alert->threshold_percent, self::SCALE) < 0) {
                continue;
            }

            // 同一アラート・同一年月は 1 通のみ（既存なら wasRecentlyCreated=false で再発火しない）。
            $notification = AlertNotification::firstOrCreate(
                ['alert_id' => $alert->id, 'year_month' => $yearMonth],
                ['triggered_at' => $triggeredAt, 'actual_percent' => $actualPercent],
            );

            if ($notification->wasRecentlyCreated) {
                $triggered[] = $notification;
            }
        }

        return $triggered;
    }

    /**
     * アラート対象の消化率を計算結果から引く。
     * category_id が null（全体アラート）なら全体消化率、カテゴリー別ならそのカテゴリーの消化率。
     * 対象カテゴリーに予算・実支出が無い場合は null（判定不能）。
     */
    private function actualPercentFor(BudgetAlert $alert, BudgetCalculationResult $result): ?string
    {
        if ($alert->category_id === null) {
            return $result->overall_usage_percent;
        }

        foreach ($result->categories as $category) {
            if ($category->category_id === $alert->category_id) {
                return $category->usage_percent;
            }
        }

        return null;
    }
}

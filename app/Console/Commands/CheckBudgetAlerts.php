<?php

namespace App\Console\Commands;

use App\Models\BudgetAlert;
use App\Services\BudgetAlertService;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * 予算アラートの閾値到達を検知する日次バッチ（F-12）。
 * 有効なアラートを持つ家族ごとに消化率を判定し、閾値到達分を alert_notifications へ記録する。
 * 冪等（同一アラート・同一年月は 1 通のみ）のため多重実行は安全。
 * スケジュールは routes/console.php に登録している。
 */
class CheckBudgetAlerts extends Command
{
    protected $signature = 'budget:check-budget-alerts {--month= : 対象年月 (Y-m)。省略時は当月} {--date= : 通知発生日時 (Y-m-d)。省略時は現在}';

    protected $description = '予算消化率が閾値に達した家族へアラート通知を記録する（日次バッチ）';

    public function handle(BudgetAlertService $alertService): int
    {
        $monthOption = $this->option('month');

        // 明示指定された --month が不正な場合は、誤月への記録を避けるため当月へ黙殺せずエラー終了する。
        if ($monthOption !== null && ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthOption)) {
            $this->error("--month は YYYY-MM 形式（月は 01-12）で指定してください: {$monthOption}");

            return self::FAILURE;
        }

        $reference = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $yearMonths = $monthOption !== null ? [$monthOption] : $this->targetMonths($reference);

        // 有効なアラートを設定している家族のみを対象にする。
        $familyIds = BudgetAlert::where('is_enabled', true)
            ->distinct()
            ->pluck('family_id');

        $total = 0;
        $failures = 0;
        foreach ($familyIds as $familyId) {
            foreach ($yearMonths as $yearMonth) {
                // 1 家族の失敗で夜間バッチ全体を止めないよう家族単位で隔離し、残りを継続する。
                try {
                    $total += count($alertService->evaluate($familyId, $yearMonth, $reference));
                } catch (Throwable $e) {
                    $failures++;
                    report($e);
                    $this->error("家族 {$familyId} / {$yearMonth} の判定に失敗しました: {$e->getMessage()}");
                }
            }
        }

        $this->info('予算アラート '.$total.' 件を検知しました（対象年月: '.implode(', ', $yearMonths).'）。');

        if ($failures > 0) {
            $this->warn("{$failures} 件の判定でエラーが発生しました（詳細はログを参照）。");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * スケジュール実行時の対象年月。当月を基本とし、月初（1 日）は前月末 02:00 以降の
     * 閾値到達を取りこぼさないよう前月も併せて判定する（通知は冪等なので二重記録しない）。
     *
     * @return string[]
     */
    private function targetMonths(CarbonInterface $reference): array
    {
        $months = [$reference->format('Y-m')];

        if ((int) $reference->day === 1) {
            $months[] = $reference->copy()->subMonth()->format('Y-m');
        }

        return $months;
    }
}

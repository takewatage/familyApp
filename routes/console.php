<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 繰り返し支出（固定費）の生成: 毎日 01:00 に支払日到来分を生成する。
// 冪等（last_generated_date で当月二重生成を防止）のため多重実行は安全。
Schedule::command('budget:generate-recurring-expenses')->dailyAt('01:00');

// 予算アラートの閾値判定: 毎日 02:00 に消化率が閾値に達した家族へ通知を記録する。
// 固定費生成（01:00）の後に走らせ、生成済み固定費を実支出に含めて判定する。
// 冪等（alert_id + year_month で当月重複通知を防止）のため多重実行は安全。
Schedule::command('budget:check-budget-alerts')->dailyAt('02:00');

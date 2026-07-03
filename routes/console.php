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

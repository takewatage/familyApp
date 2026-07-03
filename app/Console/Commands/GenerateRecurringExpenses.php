<?php

namespace App\Console\Commands;

use App\Services\RecurringExpenseGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * 支払日が到来した繰り返し支出（固定費）から支出を生成する日次バッチ。
 * スケジュールは routes/console.php に登録している。
 */
class GenerateRecurringExpenses extends Command
{
    protected $signature = 'budget:generate-recurring-expenses {--date= : 基準日 (Y-m-d)。省略時は今日}';

    protected $description = '支払日が到来した繰り返し支出から支出を生成する（日次バッチ）';

    public function handle(RecurringExpenseGenerator $generator): int
    {
        $asOf = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();

        $count = $generator->generate($asOf);

        $this->info("繰り返し支出から {$count} 件の支出を生成しました（基準日: {$asOf->toDateString()}）。");

        return self::SUCCESS;
    }
}

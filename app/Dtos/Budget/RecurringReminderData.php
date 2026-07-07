<?php

namespace App\Dtos\Budget;

use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\CamelCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * 当月の固定費支払い期限リマインダー 1 件分（F-13）。
 * is_paid: 当月に当該固定費から支出が生成済みか。
 * is_upcoming: 未払いかつ支払日が基準日から一定日数以内（期日接近の強調表示用）。
 * is_overdue: 未払いかつ支払日が基準日より前（支払い遅延の強調表示用）。
 */
#[TypeScript]
#[MapOutputName(CamelCaseMapper::class)]
class RecurringReminderData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $amount,
        public string $payment_date,
        public bool $is_paid,
        public bool $is_upcoming,
        public bool $is_overdue,
    ) {}
}

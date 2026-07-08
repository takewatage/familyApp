<?php

namespace App\Services;

use App\Dtos\Budget\RecurringReminderData;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * 固定費支払い期限リマインダーの集計（F-13）。
 * 当月に支払日が到来する有効な繰り返し支出を、支払予定日・支払済み・期日接近フラグ付きで返す。
 */
class RecurringExpenseReminderService
{
    /** 支払日がこの日数以内なら「期日接近」として強調表示する。 */
    private const UPCOMING_DAYS = 7;

    /**
     * 指定家族・年月（YYYY-MM）の固定費リマインダーを支払予定日昇順で返す。
     *
     * @param  CarbonInterface|string|null  $asOf  基準日（期日接近判定の起点。省略時は今日）
     * @return RecurringReminderData[]
     */
    public function forMonth(string $familyId, string $yearMonth, CarbonInterface|string|null $asOf = null): array
    {
        $asOf = $asOf ? Carbon::parse($asOf)->startOfDay() : Carbon::today();
        $monthStart = Carbon::parse($yearMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        // 期日接近・遅延は「今まさに進行中の月」を見ているときだけ意味を持つ。
        // 過去月/未来月を閲覧中（asOf が対象月外）は緊急フラグを立てず、支払予定と支払済みのみ示す。
        $isActionableMonth = $asOf->betweenIncluded($monthStart, $monthEnd);

        // 当月に支払済み（生成済み支出がある）繰り返し支出 ID の集合。
        $paidRecurringIds = Expense::where('family_id', $familyId)
            ->whereNotNull('recurring_expense_id')
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->pluck('recurring_expense_id')
            ->flip();

        $reminders = [];

        RecurringExpense::activeForFamily($familyId)
            ->get()
            ->each(function (RecurringExpense $recurring) use (&$reminders, $paidRecurringIds, $monthStart, $asOf, $isActionableMonth) {
                if (! $recurring->isDueInMonth($monthStart)) {
                    return;
                }

                $paymentDate = $recurring->paymentDateForMonth($monthStart);
                $isPaid = $paidRecurringIds->has($recurring->id);

                // 未払いかつ支払日が基準日〜基準日+N日の範囲にあれば期日接近（進行中の月のみ）。
                $isUpcoming = $isActionableMonth
                    && ! $isPaid
                    && $paymentDate->greaterThanOrEqualTo($asOf)
                    && $paymentDate->lessThanOrEqualTo($asOf->copy()->addDays(self::UPCOMING_DAYS));

                // 未払いかつ支払日が基準日より前なら支払い遅延（進行中の月のみ強調する）。
                $isOverdue = $isActionableMonth && ! $isPaid && $paymentDate->lessThan($asOf);

                $reminders[] = new RecurringReminderData(
                    id: $recurring->id,
                    name: $recurring->name,
                    amount: (string) $recurring->amount,
                    payment_date: $paymentDate->toDateString(),
                    is_paid: $isPaid,
                    is_upcoming: $isUpcoming,
                    is_overdue: $isOverdue,
                );
            });

        usort($reminders, fn (RecurringReminderData $a, RecurringReminderData $b) => $a->payment_date <=> $b->payment_date);

        return $reminders;
    }
}

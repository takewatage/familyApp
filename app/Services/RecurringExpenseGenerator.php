<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Models\Shop;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 繰り返し支出（固定費）から当月の支出を生成するバッチロジック。
 *
 * 冪等性の要は `last_generated_date`: 当月分を生成したら支払日を記録し、同月内の再実行では
 * 二重生成しない。日次バッチ（GenerateRecurringExpenses コマンド）から呼ばれることを想定するが、
 * 基準日を差し替えれば任意日での生成もできる（テスト・手動補完用）。
 */
class RecurringExpenseGenerator
{
    /**
     * 支払日が到来した繰り返し支出から支出を生成し、生成件数を返す。
     *
     * @param  CarbonInterface|string|null  $asOf  基準日（省略時は今日）
     */
    public function generate(CarbonInterface|string|null $asOf = null): int
    {
        $asOf = $asOf ? Carbon::parse($asOf)->startOfDay() : Carbon::today();
        $count = 0;

        // 有効かつ開始済みのものだけを対象にする。終了日・支払日到来・当月生成済みの精査は
        // 各レコードごとに generateFor で行う（終了日を asOf と単純比較すると当月内の
        // 支払日 < asOf < 終了日 のケースを取りこぼすため、クエリでは絞り込まない）。
        RecurringExpense::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $asOf->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($recurringExpenses) use ($asOf, &$count) {
                foreach ($recurringExpenses as $recurring) {
                    if ($this->generateFor($recurring, $asOf)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * 1 件の繰り返し支出について当月分の支出を生成する。生成したら true、スキップなら false。
     */
    private function generateFor(RecurringExpense $recurring, CarbonInterface $asOf): bool
    {
        $paymentDate = $this->paymentDateForMonth($recurring, $asOf);

        // 当月の支払日がまだ到来していない
        if ($paymentDate->greaterThan($asOf)) {
            return false;
        }

        // 開始日より前（当月の支払日が開始日を跨いでいない）
        if ($paymentDate->lessThan($recurring->start_date)) {
            return false;
        }

        // 終了日を過ぎている
        if ($recurring->end_date !== null && $paymentDate->greaterThan($recurring->end_date)) {
            return false;
        }

        // 当月分は生成済み（fast-path）。last_generated_date は目安であり、冪等性の権威は
        // 下の存在チェック。日次実行の大多数（当月生成済み）でクエリを省くための早期 return。
        if ($recurring->last_generated_date !== null
            && $recurring->last_generated_date->isSameMonth($paymentDate)) {
            return false;
        }

        // 冪等性の権威: 当月に同一 recurring から生成済みの支出が既にあれば二重生成しない。
        // last_generated_date が --date バックフィルで後退／書込み途中クラッシュで null のままでも、
        // ここで実データを見て塞ぐ。範囲比較（whereBetween）で expense_date の関数ラップを避ける。
        $alreadyGenerated = Expense::where('recurring_expense_id', $recurring->id)
            ->whereBetween('expense_date', [
                $paymentDate->copy()->startOfMonth()->toDateString(),
                $paymentDate->copy()->endOfMonth()->toDateString(),
            ])
            ->exists();

        if ($alreadyGenerated) {
            return false;
        }

        // 支出生成・店舗利用回数加算・marker 更新を原子的に行う。途中クラッシュで支出だけ残り
        // marker が null のまま次回二重生成されるのを防ぐ。
        DB::transaction(function () use ($recurring, $paymentDate) {
            Expense::create([
                'family_id' => $recurring->family_id,
                'member_type' => $recurring->member_type,
                'member_id' => $recurring->member_id,
                'category_id' => $recurring->category_id,
                'payment_method_id' => $recurring->payment_method_id,
                'shop_id' => $recurring->shop_id,
                'shop_name' => null,
                'amount' => $recurring->amount,
                'expense_date' => $paymentDate->toDateString(),
                'memo' => null,
                'is_recurring' => true,
                'recurring_expense_id' => $recurring->id,
            ]);

            // 手動登録（ExpenseService）と同様、店舗の利用回数を「紐付く支出数」として整合させる。
            // 未加算だと生成支出の削除時に usage_count が過小へドリフトするため、ここで +1 する。
            if ($recurring->shop_id !== null) {
                Shop::where('id', $recurring->shop_id)->increment('usage_count');
            }

            $recurring->update(['last_generated_date' => $paymentDate->toDateString()]);
        });

        return true;
    }

    /**
     * 基準日の月における支払日を返す。day_of_month が当月の日数を超える場合は月末に丸める
     * （例: 31 日指定 & 2 月 → 28/29 日）。
     */
    private function paymentDateForMonth(RecurringExpense $recurring, CarbonInterface $asOf): Carbon
    {
        $month = Carbon::parse($asOf)->startOfMonth();
        $day = min($recurring->day_of_month, $month->daysInMonth);

        return $month->day($day)->startOfDay();
    }
}

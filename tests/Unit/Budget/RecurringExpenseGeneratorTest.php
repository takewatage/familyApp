<?php

namespace Tests\Unit\Budget;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\RecurringExpense;
use App\Models\Shop;
use App\Services\RecurringExpenseGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private RecurringExpenseGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = app(RecurringExpenseGenerator::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRecurring(array $overrides = []): RecurringExpense
    {
        $family = Family::factory()->create();

        return RecurringExpense::factory()->create(array_merge([
            'family_id' => $family->id,
            'category_id' => Category::factory()->create(['family_id' => $family->id])->id,
            'payment_method_id' => PaymentMethod::factory()->create(['family_id' => $family->id])->id,
            'day_of_month' => 10,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
            'last_generated_date' => null,
        ], $overrides));
    }

    public function test_generates_expense_when_payment_day_has_arrived(): void
    {
        $recurring = $this->makeRecurring(['amount' => 50000, 'day_of_month' => 10]);

        $count = $this->generator->generate('2026-07-10');

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurring->id,
            'is_recurring' => true,
            'amount' => '50000.00',
            'expense_date' => '2026-07-10',
        ]);
        $this->assertSame('2026-07-10', $recurring->fresh()->last_generated_date->toDateString());
    }

    public function test_is_idempotent_within_same_month(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 10]);

        $first = $this->generator->generate('2026-07-10');
        // 同月内で複数回実行しても二重生成しない（last_generated_date で制御）
        $second = $this->generator->generate('2026-07-15');
        $third = $this->generator->generate('2026-07-31');

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        $this->assertSame(0, $third);
        $this->assertSame(1, Expense::where('recurring_expense_id', $recurring->id)->count());
    }

    public function test_generates_again_in_next_month(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 10]);

        $this->generator->generate('2026-07-10');
        $count = $this->generator->generate('2026-08-10');

        $this->assertSame(1, $count);
        $this->assertSame(2, Expense::where('recurring_expense_id', $recurring->id)->count());
        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurring->id,
            'expense_date' => '2026-08-10',
        ]);
    }

    public function test_skips_before_payment_day(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 20]);

        // 支払日（20 日）がまだ到来していない
        $count = $this->generator->generate('2026-07-05');

        $this->assertSame(0, $count);
        $this->assertSame(0, Expense::where('recurring_expense_id', $recurring->id)->count());
        $this->assertNull($recurring->fresh()->last_generated_date);
    }

    public function test_skips_inactive_recurring_expense(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 10, 'is_active' => false]);

        $count = $this->generator->generate('2026-07-10');

        $this->assertSame(0, $count);
        $this->assertSame(0, Expense::where('recurring_expense_id', $recurring->id)->count());
    }

    public function test_skips_before_start_date(): void
    {
        $recurring = $this->makeRecurring([
            'day_of_month' => 10,
            'start_date' => '2026-07-15',
        ]);

        // 当月の支払日（07-10）が開始日（07-15）より前
        $count = $this->generator->generate('2026-07-31');

        $this->assertSame(0, $count);
        $this->assertSame(0, Expense::where('recurring_expense_id', $recurring->id)->count());
    }

    public function test_skips_after_end_date(): void
    {
        $recurring = $this->makeRecurring([
            'day_of_month' => 10,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        // 終了日（06-30）を過ぎた月の支払日（07-10）は対象外
        $count = $this->generator->generate('2026-07-10');

        $this->assertSame(0, $count);
        $this->assertSame(0, Expense::where('recurring_expense_id', $recurring->id)->count());
    }

    public function test_generates_on_end_date_month(): void
    {
        $recurring = $this->makeRecurring([
            'day_of_month' => 10,
            'end_date' => '2026-07-20',
        ]);

        // 支払日（07-10） <= 終了日（07-20）なら当月分は生成する
        $count = $this->generator->generate('2026-07-31');

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurring->id,
            'expense_date' => '2026-07-10',
        ]);
    }

    public function test_clamps_day_of_month_to_month_end(): void
    {
        $recurring = $this->makeRecurring([
            'day_of_month' => 31,
            'start_date' => '2026-01-01',
        ]);

        // 2 月は 28 日までなので支払日は月末に丸める
        $count = $this->generator->generate('2026-02-28');

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurring->id,
            'expense_date' => '2026-02-28',
        ]);
    }

    public function test_increments_shop_usage_count_when_shop_linked(): void
    {
        $family = Family::factory()->create();
        $shop = Shop::factory()->create(['family_id' => $family->id, 'usage_count' => 3]);
        $recurring = RecurringExpense::factory()->create([
            'family_id' => $family->id,
            'category_id' => Category::factory()->create(['family_id' => $family->id])->id,
            'payment_method_id' => PaymentMethod::factory()->create(['family_id' => $family->id])->id,
            'shop_id' => $shop->id,
            'day_of_month' => 10,
            'start_date' => '2026-01-01',
        ]);

        $this->generator->generate('2026-07-10');

        $this->assertSame(4, (int) $shop->fresh()->usage_count);
        $this->assertDatabaseHas('expenses', [
            'recurring_expense_id' => $recurring->id,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_backfilling_earlier_month_does_not_duplicate_later_month(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 15, 'start_date' => '2026-01-01']);

        // 2 月を通常生成（marker=02-15）
        $this->generator->generate('2026-02-15');
        // 1 月を --date でバックフィル（marker が後退しうる）
        $this->generator->generate('2026-01-25');
        // 再度 2 月の日次実行
        $this->generator->generate('2026-02-16');

        // marker 後退で 2 月が再武装されず、2 月・1 月ともに 1 件ずつ（二重生成しない）
        $this->assertSame(1, Expense::where('recurring_expense_id', $recurring->id)
            ->whereBetween('expense_date', ['2026-02-01', '2026-02-28'])->count());
        $this->assertSame(1, Expense::where('recurring_expense_id', $recurring->id)
            ->whereBetween('expense_date', ['2026-01-01', '2026-01-31'])->count());
    }

    public function test_does_not_regenerate_when_expense_exists_but_marker_is_null(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 10, 'start_date' => '2026-01-01']);

        // 書込み途中クラッシュを模擬: 支出は生成済みだが last_generated_date は null のまま
        Expense::create([
            'family_id' => $recurring->family_id,
            'category_id' => $recurring->category_id,
            'payment_method_id' => $recurring->payment_method_id,
            'amount' => $recurring->amount,
            'expense_date' => '2026-07-10',
            'is_recurring' => true,
            'recurring_expense_id' => $recurring->id,
        ]);

        // 存在チェックが実データを見て二重生成を塞ぐ
        $count = $this->generator->generate('2026-07-15');

        $this->assertSame(0, $count);
        $this->assertSame(1, Expense::where('recurring_expense_id', $recurring->id)->count());
    }
}

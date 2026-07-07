<?php

namespace Tests\Unit\Budget;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\RecurringExpense;
use App\Services\BudgetCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetCalculationService $service;

    private Family $family;

    private Category $category;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetCalculationService::class);
        $this->family = Family::factory()->create();
        $this->category = Category::factory()->create(['family_id' => $this->family->id]);
        $this->paymentMethod = PaymentMethod::factory()->create(['family_id' => $this->family->id]);
    }

    private function makeExpense(string $amount, string $date, ?string $categoryId = null): Expense
    {
        return Expense::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $categoryId ?? $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => $amount,
            'expense_date' => $date,
        ]);
    }

    public function test_totals_and_category_usage_are_calculated(): void
    {
        $budget = Budget::factory()->create([
            'family_id' => $this->family->id,
            'year_month' => '2026-07',
            'total_income' => 300000,
            'saving_target' => 50000,
        ]);
        BudgetCategory::factory()->create([
            'budget_id' => $budget->id,
            'category_id' => $this->category->id,
            'amount' => 10000,
        ]);

        // 当月支出 8000（消化率 80%）+ 対象外月の支出は集計しない
        $this->makeExpense('8000.00', '2026-07-10');
        $this->makeExpense('9999.00', '2026-08-01');

        $result = $this->service->calculate($this->family->id, '2026-07');

        $this->assertSame('2026-07', $result->year_month);
        $this->assertSame('300000.00', $result->total_income);
        $this->assertSame('8000.00', $result->total_expense);
        $this->assertSame('50000.00', $result->saving_target);
        $this->assertSame('10000.00', $result->category_budget_total);

        $this->assertCount(1, $result->categories);
        $usage = $result->categories[0];
        $this->assertSame($this->category->id, $usage->category_id);
        $this->assertSame('10000.00', $usage->budget_amount);
        $this->assertSame('8000.00', $usage->actual_amount);
        $this->assertSame('2000.00', $usage->remaining);
        $this->assertSame('80.00', $usage->usage_percent);
    }

    public function test_usage_percent_is_null_when_no_budget(): void
    {
        // 予算行なし・実支出のみ → 消化率は算出不能（null）
        $this->makeExpense('5000.00', '2026-07-10');

        $result = $this->service->calculate($this->family->id, '2026-07');

        $this->assertCount(1, $result->categories);
        $this->assertNull($result->categories[0]->usage_percent);
        $this->assertSame('-5000.00', $result->categories[0]->remaining);
        // 収入未設定なら全体消化率も null
        $this->assertNull($result->overall_usage_percent);
    }

    public function test_fixed_cost_total_counts_due_recurring_regardless_of_generation(): void
    {
        RecurringExpense::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 12000,
            'day_of_month' => 25,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ]);
        // 終了済みの固定費は当月の固定費合計に含めない
        RecurringExpense::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 99999,
            'day_of_month' => 25,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

        $result = $this->service->calculate($this->family->id, '2026-07');

        $this->assertSame('12000.00', $result->fixed_cost_total);
    }

    public function test_discretionary_and_possible_saving(): void
    {
        $budget = Budget::factory()->create([
            'family_id' => $this->family->id,
            'year_month' => '2026-07',
            'total_income' => 300000,
            'saving_target' => 50000,
        ]);
        BudgetCategory::factory()->create([
            'budget_id' => $budget->id,
            'category_id' => $this->category->id,
            'amount' => 80000,
        ]);
        RecurringExpense::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100000,
            'day_of_month' => 10,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);
        $this->makeExpense('120000.00', '2026-07-05');

        $result = $this->service->calculate($this->family->id, '2026-07');

        // 自由に使えるお金 = 300000 - 100000(固定費) - 80000(生活費予算) - 50000(貯金目標)
        $this->assertSame('70000.00', $result->discretionary);
        // 貯金可能額 = 300000 - 120000(実支出)
        $this->assertSame('180000.00', $result->possible_saving);
        // 全体消化率 = 120000 / 300000 = 40%
        $this->assertSame('40.00', $result->overall_usage_percent);
    }

    public function test_excludes_other_family_expenses(): void
    {
        $other = Family::factory()->create();
        Expense::factory()->create([
            'family_id' => $other->id,
            'category_id' => Category::factory()->create(['family_id' => $other->id])->id,
            'payment_method_id' => PaymentMethod::factory()->create(['family_id' => $other->id])->id,
            'amount' => '77777.00',
            'expense_date' => '2026-07-10',
        ]);

        $result = $this->service->calculate($this->family->id, '2026-07');

        $this->assertSame('0.00', $result->total_expense);
        $this->assertCount(0, $result->categories);
    }
}

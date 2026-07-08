<?php

namespace Tests\Unit\Budget;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\RecurringExpense;
use App\Services\RecurringExpenseReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecurringExpenseReminderService $service;

    private Family $family;

    private Category $category;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecurringExpenseReminderService::class);
        $this->family = Family::factory()->create();
        $this->category = Category::factory()->create(['family_id' => $this->family->id]);
        $this->paymentMethod = PaymentMethod::factory()->create(['family_id' => $this->family->id]);
    }

    private function makeRecurring(array $overrides = []): RecurringExpense
    {
        return RecurringExpense::factory()->create(array_merge([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'day_of_month' => 25,
            'start_date' => '2026-01-01',
            'end_date' => null,
            'is_active' => true,
        ], $overrides));
    }

    public function test_returns_due_recurring_with_payment_date(): void
    {
        $recurring = $this->makeRecurring(['name' => '家賃', 'amount' => 80000, 'day_of_month' => 25]);

        // 基準日 07-01: 支払日 07-25 は 7 日以内ではない（未接近）
        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-07-01');

        $this->assertCount(1, $reminders);
        $this->assertSame($recurring->id, $reminders[0]->id);
        $this->assertSame('家賃', $reminders[0]->name);
        $this->assertSame('80000.00', $reminders[0]->amount);
        $this->assertSame('2026-07-25', $reminders[0]->payment_date);
        $this->assertFalse($reminders[0]->is_paid);
        $this->assertFalse($reminders[0]->is_upcoming);
    }

    public function test_marks_upcoming_within_window(): void
    {
        $this->makeRecurring(['day_of_month' => 25]);

        // 基準日 07-20: 支払日 07-25 は 5 日後（7 日以内）→ 期日接近
        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-07-20');

        $this->assertTrue($reminders[0]->is_upcoming);
    }

    public function test_marks_overdue_when_unpaid_and_past_due(): void
    {
        $this->makeRecurring(['day_of_month' => 5]); // 支払日 2026-07-05

        // 基準日 07-20: 支払日 07-05 は過去かつ未払い → 支払い遅延
        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-07-20');

        $this->assertTrue($reminders[0]->is_overdue);
        $this->assertFalse($reminders[0]->is_upcoming);
        $this->assertFalse($reminders[0]->is_paid);
    }

    public function test_suppresses_overdue_upcoming_when_viewing_non_current_month(): void
    {
        $this->makeRecurring(['day_of_month' => 5]); // 支払日 2026-07-05

        // 8 月に居る状態で 7 月（過去月）を閲覧: 支払予定は出すが緊急フラグは立てない
        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-08-10');

        $this->assertCount(1, $reminders);
        $this->assertFalse($reminders[0]->is_overdue);
        $this->assertFalse($reminders[0]->is_upcoming);
        $this->assertFalse($reminders[0]->is_paid);
    }

    public function test_marks_paid_when_expense_generated(): void
    {
        $recurring = $this->makeRecurring(['day_of_month' => 25]);
        Expense::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'expense_date' => '2026-07-25',
            'is_recurring' => true,
            'recurring_expense_id' => $recurring->id,
        ]);

        // 支払済みなら接近ウィンドウ内でも is_upcoming=false
        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-07-20');

        $this->assertTrue($reminders[0]->is_paid);
        $this->assertFalse($reminders[0]->is_upcoming);
    }

    public function test_excludes_recurring_not_due_in_month(): void
    {
        // 終了日を過ぎた固定費は当月リマインダーに含めない
        $this->makeRecurring(['day_of_month' => 25, 'end_date' => '2026-06-30']);

        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-07-01');

        $this->assertCount(0, $reminders);
    }

    public function test_orders_by_payment_date(): void
    {
        $this->makeRecurring(['name' => 'A', 'day_of_month' => 25]);
        $this->makeRecurring(['name' => 'B', 'day_of_month' => 5]);
        $this->makeRecurring(['name' => 'C', 'day_of_month' => 15]);

        $reminders = $this->service->forMonth($this->family->id, '2026-07', '2026-07-01');

        $this->assertSame(['B', 'C', 'A'], array_map(fn ($r) => $r->name, $reminders));
    }
}

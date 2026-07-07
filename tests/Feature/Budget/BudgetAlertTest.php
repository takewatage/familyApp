<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\BudgetAlert;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Services\BudgetAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAlertTest extends TestCase
{
    use RefreshDatabase;

    private BudgetAlertService $service;

    private Family $family;

    private Category $category;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetAlertService::class);
        $this->family = Family::factory()->create();
        $this->category = Category::factory()->create(['family_id' => $this->family->id]);
        $this->paymentMethod = PaymentMethod::factory()->create(['family_id' => $this->family->id]);
    }

    private function budgetWithCategory(int $amount): void
    {
        $budget = Budget::factory()->create([
            'family_id' => $this->family->id,
            'year_month' => '2026-07',
            'total_income' => 300000,
            'saving_target' => 0,
        ]);
        BudgetCategory::factory()->create([
            'budget_id' => $budget->id,
            'category_id' => $this->category->id,
            'amount' => $amount,
        ]);
    }

    private function makeExpense(string $amount): void
    {
        Expense::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => $amount,
            'expense_date' => '2026-07-10',
        ]);
    }

    public function test_records_notification_when_category_threshold_reached(): void
    {
        $this->budgetWithCategory(10000);
        $this->makeExpense('8500.00'); // 85% >= 80%
        $alert = BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'threshold_percent' => 80,
            'is_enabled' => true,
        ]);

        $triggered = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');

        $this->assertCount(1, $triggered);
        $this->assertDatabaseHas('alert_notifications', [
            'alert_id' => $alert->id,
            'year_month' => '2026-07',
            'actual_percent' => '85.00',
        ]);
    }

    public function test_does_not_record_when_below_threshold(): void
    {
        $this->budgetWithCategory(10000);
        $this->makeExpense('7000.00'); // 70% < 80%
        BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'threshold_percent' => 80,
        ]);

        $triggered = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');

        $this->assertCount(0, $triggered);
        $this->assertDatabaseCount('alert_notifications', 0);
    }

    public function test_does_not_fire_when_rounding_would_cross_threshold(): void
    {
        $this->budgetWithCategory(100000);
        // 79.996% → 切り捨て 79.99% < 80%。half-up 丸めなら 80.00% に切り上がり誤発火する。
        $this->makeExpense('79996.00');
        BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'threshold_percent' => 80,
        ]);

        $triggered = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');

        $this->assertCount(0, $triggered);
        $this->assertDatabaseCount('alert_notifications', 0);
    }

    public function test_is_idempotent_within_same_month(): void
    {
        $this->budgetWithCategory(10000);
        $this->makeExpense('9000.00');
        $alert = BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'threshold_percent' => 80,
        ]);

        $first = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');
        $second = $this->service->evaluate($this->family->id, '2026-07', '2026-07-20');

        $this->assertCount(1, $first);
        $this->assertCount(0, $second); // 同月・同一アラートは再発火しない
        $this->assertSame(1, $alert->notifications()->count());
    }

    public function test_skips_disabled_alert(): void
    {
        $this->budgetWithCategory(10000);
        $this->makeExpense('9500.00');
        BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'threshold_percent' => 80,
            'is_enabled' => false,
        ]);

        $triggered = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');

        $this->assertCount(0, $triggered);
        $this->assertDatabaseCount('alert_notifications', 0);
    }

    public function test_skips_category_alert_without_budget(): void
    {
        // 予算行なし → 消化率が定義できずアラート対象外
        $this->makeExpense('9500.00');
        BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => $this->category->id,
            'threshold_percent' => 80,
        ]);

        $triggered = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');

        $this->assertCount(0, $triggered);
    }

    public function test_overall_alert_uses_income_denominator(): void
    {
        // 収入 300000 に対し実支出 240000 = 80% で全体アラート発火
        Budget::factory()->create([
            'family_id' => $this->family->id,
            'year_month' => '2026-07',
            'total_income' => 300000,
            'saving_target' => 0,
        ]);
        $this->makeExpense('240000.00');
        $alert = BudgetAlert::factory()->create([
            'family_id' => $this->family->id,
            'category_id' => null,
            'threshold_percent' => 80,
        ]);

        $triggered = $this->service->evaluate($this->family->id, '2026-07', '2026-07-10');

        $this->assertCount(1, $triggered);
        $this->assertDatabaseHas('alert_notifications', [
            'alert_id' => $alert->id,
            'actual_percent' => '80.00',
        ]);
    }
}

<?php

namespace Tests\Feature\Budget;

use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BudgetDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, family: Family}
     */
    private function makeFamilyContext(): array
    {
        $user = User::factory()->create();
        $family = Family::factory()->create(['owner_id' => $user->id]);

        DB::table('family_user')->insert([
            'id' => (string) Str::uuid(),
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['user' => $user, 'family' => $family];
    }

    public function test_index_renders_calculation_for_month(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $budget = Budget::create([
            'family_id' => $family->id,
            'year_month' => '2026-07',
            'total_income' => 300000,
            'saving_target' => 50000,
        ]);
        BudgetCategory::create([
            'budget_id' => $budget->id,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);
        Expense::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'amount' => '8000.00',
            'expense_date' => '2026-07-10',
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.dashboard', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget/Dashboard')
                ->where('yearMonth', '2026-07')
                ->where('calculation.totalIncome', '300000.00')
                ->where('calculation.totalExpense', '8000.00')
                ->where('calculation.possibleSaving', '292000.00')
                ->where('calculation.overallUsagePercent', '2.66')
                ->where('calculation.categories.0.usagePercent', '80.00'));
    }

    public function test_index_includes_recurring_reminders(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        RecurringExpense::factory()->create([
            'family_id' => $family->id,
            'category_id' => Category::factory()->create(['family_id' => $family->id])->id,
            'payment_method_id' => PaymentMethod::factory()->create(['family_id' => $family->id])->id,
            'name' => '家賃',
            'amount' => 80000,
            'day_of_month' => 25,
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.dashboard', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget/Dashboard')
                ->has('reminders', 1)
                ->where('reminders.0.name', '家賃')
                ->where('reminders.0.paymentDate', '2026-07-25'));
    }

    public function test_index_includes_inactive_category_for_display(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        // 予算設定後に無効化されたカテゴリー（計算には残るが有効選択肢には出ない）
        $category = Category::factory()->create([
            'family_id' => $family->id,
            'name' => '旧カテゴリー',
            'is_active' => false,
        ]);
        $budget = Budget::create([
            'family_id' => $family->id,
            'year_month' => '2026-07',
            'total_income' => 0,
            'saving_target' => 0,
        ]);
        BudgetCategory::create([
            'budget_id' => $budget->id,
            'category_id' => $category->id,
            'amount' => 10000,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.dashboard', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget/Dashboard')
                ->where('calculation.categories.0.categoryId', $category->id)
                // 無効化済みでも名前引き当てのため categories に補完される
                ->has('categories', 1)
                ->where('categories.0.id', $category->id)
                ->where('categories.0.name', '旧カテゴリー'));
    }

    public function test_index_excludes_other_family_data(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $other = Family::factory()->create();
        $otherCategory = Category::factory()->create(['family_id' => $other->id]);
        Expense::factory()->create([
            'family_id' => $other->id,
            'category_id' => $otherCategory->id,
            'payment_method_id' => PaymentMethod::factory()->create(['family_id' => $other->id])->id,
            'amount' => '99999.00',
            'expense_date' => '2026-07-10',
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.dashboard', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget/Dashboard')
                ->where('calculation.totalExpense', '0.00')
                ->has('calculation.categories', 0));
    }
}

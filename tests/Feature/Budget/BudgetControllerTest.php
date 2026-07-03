<?php

namespace Tests\Feature\Budget;

use App\Models\AlertNotification;
use App\Models\Budget;
use App\Models\BudgetAlert;
use App\Models\BudgetCategory;
use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
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

    public function test_show_renders_for_member(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.budgets.show', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget/BudgetSettings')
                ->where('yearMonth', '2026-07')
                ->where('totalIncome', '0.00')
                ->where('savingTarget', '0.00'));
    }

    public function test_show_reflects_existing_budget_of_current_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);

        $budget = Budget::create([
            'family_id' => $family->id,
            'year_month' => '2026-07',
            'total_income' => 300000,
            'saving_target' => 50000,
        ]);
        BudgetCategory::create([
            'budget_id' => $budget->id,
            'category_id' => $category->id,
            'amount' => 40000,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.budgets.show', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('totalIncome', '300000.00')
                ->where('savingTarget', '50000.00')
                ->has('categoryBudgets', 1)
                ->where('categoryBudgets.0.categoryId', $category->id)
                ->where('categoryBudgets.0.amount', '40000.00'));
    }

    public function test_show_does_not_leak_other_family_budget(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        Budget::create([
            'family_id' => $otherFamily->id,
            'year_month' => '2026-07',
            'total_income' => 999999,
            'saving_target' => 0,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.budgets.show', ['month' => '2026-07']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('totalIncome', '0.00'));
    }

    public function test_store_upserts_budget_for_month(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        // 1 回目: 作成
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.store'), [
                'year_month' => '2026-07',
                'total_income' => '300000',
                'saving_target' => '50000',
            ])->assertSessionHasNoErrors()->assertRedirect();

        // 2 回目: 同一年月は UPDATE（UNIQUE 制約で重複行を作らない）
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.store'), [
                'year_month' => '2026-07',
                'total_income' => '320000',
                'saving_target' => '60000',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseCount('budgets', 1);
        $this->assertDatabaseHas('budgets', [
            'family_id' => $family->id,
            'year_month' => '2026-07',
            'total_income' => '320000.00',
            'saving_target' => '60000.00',
        ]);
    }

    public function test_store_validates_year_month_range(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.store'), [
                'year_month' => '2026-13',
                'total_income' => '1000',
                'saving_target' => '0',
            ])->assertSessionHasErrors('year_month');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_store_categories_creates_budget_and_upserts_amounts(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);

        // 予算行が無い状態で先にカテゴリー別予算を保存 → budget 行が自動生成される
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.categories'), [
                'year_month' => '2026-07',
                'category_budgets' => [
                    ['category_id' => $category->id, 'amount' => '40000'],
                ],
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('budgets', ['family_id' => $family->id, 'year_month' => '2026-07']);
        $this->assertDatabaseHas('budget_categories', ['category_id' => $category->id, 'amount' => '40000.00']);

        // 再保存で更新（UNIQUE budget_id+category_id、重複行を作らない）
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.categories'), [
                'year_month' => '2026-07',
                'category_budgets' => [
                    ['category_id' => $category->id, 'amount' => '45000'],
                ],
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('budget_categories', 1);
        $this->assertDatabaseHas('budget_categories', ['category_id' => $category->id, 'amount' => '45000.00']);
    }

    public function test_store_categories_rejects_category_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreignCategory = Category::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.categories'), [
                'year_month' => '2026-07',
                'category_budgets' => [
                    ['category_id' => $foreignCategory->id, 'amount' => '40000'],
                ],
            ])->assertSessionHasErrors('category_budgets.0.category_id');

        $this->assertDatabaseCount('budget_categories', 0);
    }

    public function test_store_alerts_syncs_create_update_delete(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);

        // 初回: 全体 + カテゴリー別の 2 件
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => null, 'threshold_percent' => 80, 'is_enabled' => true],
                    ['category_id' => $category->id, 'threshold_percent' => 90, 'is_enabled' => true],
                ],
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseCount('budget_alerts', 2);

        // 再送信: 全体のみ（カテゴリー別は削除、全体は閾値更新）
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => null, 'threshold_percent' => 70, 'is_enabled' => false],
                ],
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('budget_alerts', 1);
        $this->assertDatabaseHas('budget_alerts', [
            'family_id' => $family->id,
            'category_id' => null,
            'threshold_percent' => 70,
            'is_enabled' => false,
        ]);
    }

    public function test_store_alerts_preserves_notification_history_for_unchanged_alert(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        // 全体アラートを作成
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => null, 'threshold_percent' => 80, 'is_enabled' => true],
                ],
            ])->assertSessionHasNoErrors();

        $alert = BudgetAlert::where('family_id', $family->id)->whereNull('category_id')->firstOrFail();
        AlertNotification::create([
            'alert_id' => $alert->id,
            'year_month' => '2026-07',
            'triggered_at' => now(),
            'actual_percent' => 82.5,
        ]);

        // 同じ全体アラートの閾値だけ変更して再保存 → alert 行（と通知履歴）は cascade 削除されない
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => null, 'threshold_percent' => 75, 'is_enabled' => true],
                ],
            ])->assertSessionHasNoErrors();

        $this->assertSame($alert->id, BudgetAlert::where('family_id', $family->id)->whereNull('category_id')->firstOrFail()->id);
        $this->assertDatabaseCount('alert_notifications', 1);
        $this->assertDatabaseHas('alert_notifications', [
            'alert_id' => $alert->id,
            'year_month' => '2026-07',
        ]);
    }

    public function test_store_alerts_rejects_category_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreignCategory = Category::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => $foreignCategory->id, 'threshold_percent' => 80, 'is_enabled' => true],
                ],
            ])->assertSessionHasErrors('alerts.0.category_id');

        $this->assertDatabaseCount('budget_alerts', 0);
    }

    public function test_store_alerts_validates_threshold_range(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => null, 'threshold_percent' => 150, 'is_enabled' => true],
                ],
            ])->assertSessionHasErrors('alerts.0.threshold_percent');

        $this->assertDatabaseCount('budget_alerts', 0);
    }

    public function test_store_alerts_rejects_duplicate_target(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        // 全体アラートを 2 件送ると updateOrCreate で片方が黙って消えるため、distinct で弾く
        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => null, 'threshold_percent' => 80, 'is_enabled' => true],
                    ['category_id' => null, 'threshold_percent' => 60, 'is_enabled' => true],
                ],
            ])->assertSessionHasErrors('alerts');

        $this->assertDatabaseCount('budget_alerts', 0);
    }

    public function test_store_alerts_allows_saving_alert_for_inactive_category(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        // カテゴリー無効化後もそのアラートを再保存できる（is_active を検証で要求しない）
        $category = Category::factory()->create(['family_id' => $family->id, 'is_active' => false]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.alerts'), [
                'alerts' => [
                    ['category_id' => $category->id, 'threshold_percent' => 80, 'is_enabled' => true],
                ],
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('budget_alerts', [
            'family_id' => $family->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_store_categories_rejects_duplicate_category(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.budgets.categories'), [
                'year_month' => '2026-07',
                'category_budgets' => [
                    ['category_id' => $category->id, 'amount' => '1000'],
                    ['category_id' => $category->id, 'amount' => '2000'],
                ],
            ])->assertSessionHasErrors('category_budgets.0.category_id');

        $this->assertDatabaseCount('budget_categories', 0);
    }
}

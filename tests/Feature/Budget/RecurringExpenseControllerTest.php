<?php

namespace Tests\Feature\Budget;

use App\Models\Category;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\RecurringExpense;
use App\Models\Shop;
use App\Models\User;
use App\Models\VirtualUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecurringExpenseControllerTest extends TestCase
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

    public function test_index_renders_for_member(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.recurring-expenses.index'))
            ->assertOk();
    }

    public function test_index_only_lists_active_of_current_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $active = RecurringExpense::factory()->create(['family_id' => $family->id, 'is_active' => true, 'name' => '家賃']);
        RecurringExpense::factory()->create(['family_id' => $family->id, 'is_active' => false, 'name' => '解約済み']);
        RecurringExpense::factory()->create(['family_id' => $otherFamily->id, 'is_active' => true, 'name' => '他家族']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.recurring-expenses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Budget/RecurringExpenses')
                ->has('recurringExpenses', 1)
                ->where('recurringExpenses.0.id', $active->id));
    }

    public function test_store_creates_recurring_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.recurring-expenses.store'), [
                'name' => '家賃',
                'amount' => '85000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 27,
                'start_date' => '2026-07-01',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('recurring_expenses', [
            'family_id' => $family->id,
            'name' => '家賃',
            'day_of_month' => 27,
            'is_active' => true,
            'last_generated_date' => null,
        ]);
    }

    public function test_store_validates_day_of_month_range(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.recurring-expenses.store'), [
                'name' => '不正',
                'amount' => '1000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 32,
                'start_date' => '2026-07-01',
            ])->assertSessionHasErrors('day_of_month');

        $this->assertDatabaseCount('recurring_expenses', 0);
    }

    public function test_store_rejects_end_date_before_start_date(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.recurring-expenses.store'), [
                'name' => '逆転',
                'amount' => '1000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 10,
                'start_date' => '2026-07-01',
                'end_date' => '2026-06-01',
            ])->assertSessionHasErrors('end_date');
    }

    public function test_store_rejects_category_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $foreignCategory = Category::factory()->create(['family_id' => $otherFamily->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.recurring-expenses.store'), [
                'name' => '越境',
                'amount' => '1000',
                'category_id' => $foreignCategory->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 10,
                'start_date' => '2026-07-01',
            ])->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('recurring_expenses', 0);
    }

    public function test_store_rejects_member_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreignVirtual = VirtualUser::create(['family_id' => $otherFamily->id, 'name' => '他家族の子']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.recurring-expenses.store'), [
                'name' => '越境担当者',
                'amount' => '1000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 10,
                'start_date' => '2026-07-01',
                'member_type' => VirtualUser::class,
                'member_id' => $foreignVirtual->id,
            ])->assertSessionHasErrors('member_id');

        $this->assertDatabaseCount('recurring_expenses', 0);
    }

    public function test_update_modifies_recurring_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $recurring = RecurringExpense::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $pm->id,
            'name' => '旧名',
            'amount' => 1000,
            'day_of_month' => 10,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.recurring-expenses.update', $recurring->id), [
                'name' => '新名',
                'amount' => '2000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 25,
                'start_date' => '2026-07-01',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $recurring->refresh();
        $this->assertSame('新名', $recurring->name);
        $this->assertSame('2000.00', (string) $recurring->amount);
        $this->assertSame(25, $recurring->day_of_month);
    }

    public function test_update_rejects_other_family_recurring_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreign = RecurringExpense::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.recurring-expenses.update', $foreign->id), [
                'name' => '乗っ取り',
                'amount' => '2000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'day_of_month' => 25,
                'start_date' => '2026-07-01',
            ])->assertNotFound();
    }

    public function test_destroy_deactivates_recurring_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $recurring = RecurringExpense::factory()->create([
            'family_id' => $family->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.recurring-expenses.destroy', $recurring->id))
            ->assertRedirect();

        // 物理削除ではなく無効化
        $this->assertDatabaseHas('recurring_expenses', [
            'id' => $recurring->id,
            'is_active' => false,
        ]);
    }

    public function test_destroy_rejects_other_family_recurring_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = RecurringExpense::factory()->create(['family_id' => $otherFamily->id, 'is_active' => true]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.recurring-expenses.destroy', $foreign->id))
            ->assertNotFound();

        $this->assertDatabaseHas('recurring_expenses', [
            'id' => $foreign->id,
            'is_active' => true,
        ]);
    }

    public function test_store_can_assign_shop_of_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $shop = Shop::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.recurring-expenses.store'), [
                'name' => '定期便',
                'amount' => '3000',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'shop_id' => $shop->id,
                'day_of_month' => 5,
                'start_date' => '2026-07-01',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('recurring_expenses', [
            'family_id' => $family->id,
            'name' => '定期便',
            'shop_id' => $shop->id,
        ]);
    }
}

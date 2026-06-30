<?php

namespace Tests\Feature\Budget;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\Shop;
use App\Models\User;
use App\Models\VirtualUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 家族・所属ユーザーを作成し、current family をセッションに設定して返す。
     *
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

    public function test_authenticated_user_can_store_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '1500',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'memo' => 'テスト支出',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('expenses', [
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'memo' => 'テスト支出',
        ]);
    }

    public function test_store_requires_amount(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_existing_shop_name_links_and_increments_usage(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $shop = Shop::factory()->create(['family_id' => $family->id, 'name' => 'スーパーA', 'usage_count' => 2]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '980',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'shop_name' => 'スーパーA',
            ])->assertSessionHasNoErrors();

        $expense = Expense::first();
        $this->assertSame($shop->id, $expense->shop_id);
        $this->assertNull($expense->shop_name);
        $this->assertSame(3, $shop->fresh()->usage_count);
    }

    public function test_unregistered_shop_name_is_kept_as_text(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '500',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'shop_name' => '新規コンビニ',
            ])->assertSessionHasNoErrors();

        $expense = Expense::first();
        $this->assertNull($expense->shop_id);
        $this->assertSame('新規コンビニ', $expense->shop_name);
        $this->assertDatabaseCount('shops', 0);
    }

    public function test_can_assign_virtual_user_as_member(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $virtual = VirtualUser::create(['family_id' => $family->id, 'name' => '太郎']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '300',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'member_type' => VirtualUser::class,
                'member_id' => $virtual->id,
            ])->assertSessionHasNoErrors();

        $expense = Expense::first();
        $this->assertSame(VirtualUser::class, $expense->member_type);
        $this->assertSame($virtual->id, $expense->member_id);
        $this->assertInstanceOf(VirtualUser::class, $expense->member);
    }

    public function test_user_cannot_update_expense_of_another_family(): void
    {
        ['user' => $userA, 'family' => $familyA] = $this->makeFamilyContext();
        ['family' => $familyB] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $familyB->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $familyB->id]);
        $expense = Expense::factory()->create([
            'family_id' => $familyB->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->actingAs($userA)
            ->withSession(['current_family_id' => $familyA->id])
            ->patch(route('budget.expenses.update', $expense->id), [
                'amount' => '9999',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
            ]);

        $response->assertNotFound();
    }

    public function test_user_cannot_delete_expense_of_another_family(): void
    {
        ['user' => $userA, 'family' => $familyA] = $this->makeFamilyContext();
        ['family' => $familyB] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $familyB->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $familyB->id]);
        $expense = Expense::factory()->create([
            'family_id' => $familyB->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $response = $this->actingAs($userA)
            ->withSession(['current_family_id' => $familyA->id])
            ->delete(route('budget.expenses.destroy', $expense->id));

        $response->assertNotFound();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_user_can_delete_own_expense(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $expense = Expense::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.expenses.destroy', $expense->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    public function test_index_renders_for_member(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.expenses.index'))
            ->assertOk();
    }

    public function test_store_rejects_category_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreignCategory = Category::factory()->create(['family_id' => $otherFamily->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '1500',
                'category_id' => $foreignCategory->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
            ]);

        $response->assertSessionHasErrors('category_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_store_rejects_payment_method_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $family->id]);
        $foreignPaymentMethod = PaymentMethod::factory()->create(['family_id' => $otherFamily->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '1500',
                'category_id' => $category->id,
                'payment_method_id' => $foreignPaymentMethod->id,
                'expense_date' => '2026-06-20',
            ]);

        $response->assertSessionHasErrors('payment_method_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_store_rejects_shop_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreignShop = Shop::factory()->create(['family_id' => $otherFamily->id]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '1500',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'shop_id' => $foreignShop->id,
            ]);

        $response->assertSessionHasErrors('shop_id');
        $this->assertDatabaseCount('expenses', 0);
        $this->assertSame(0, (int) $foreignShop->fresh()->usage_count);
    }

    public function test_store_rejects_member_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();

        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreignVirtual = VirtualUser::create(['family_id' => $otherFamily->id, 'name' => '他家族の子']);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '1500',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'member_type' => VirtualUser::class,
                'member_id' => $foreignVirtual->id,
            ]);

        $response->assertSessionHasErrors('member_id');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_update_switching_shop_moves_usage_count(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $shopA = Shop::factory()->create(['family_id' => $family->id, 'usage_count' => 1]);
        $shopB = Shop::factory()->create(['family_id' => $family->id, 'usage_count' => 0]);
        $expense = Expense::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'shop_id' => $shopA->id,
            'shop_name' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.expenses.update', $expense->id), [
                'amount' => '1000',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'shop_id' => $shopB->id,
            ])->assertSessionHasNoErrors();

        $this->assertSame(0, (int) $shopA->fresh()->usage_count);
        $this->assertSame(1, (int) $shopB->fresh()->usage_count);
    }

    public function test_update_keeping_same_shop_does_not_change_usage_count(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $shop = Shop::factory()->create(['family_id' => $family->id, 'usage_count' => 1]);
        $expense = Expense::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'shop_id' => $shop->id,
            'shop_name' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.expenses.update', $expense->id), [
                'amount' => '1000',
                'category_id' => $category->id,
                'payment_method_id' => $paymentMethod->id,
                'expense_date' => '2026-06-20',
                'shop_id' => $shop->id,
            ])->assertSessionHasNoErrors();

        $this->assertSame(1, (int) $shop->fresh()->usage_count);
    }

    public function test_delete_decrements_shop_usage_count(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $paymentMethod = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $shop = Shop::factory()->create(['family_id' => $family->id, 'usage_count' => 2]);
        $expense = Expense::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'shop_id' => $shop->id,
            'shop_name' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.expenses.destroy', $expense->id))
            ->assertRedirect();

        $this->assertSame(1, (int) $shop->fresh()->usage_count);
    }

    public function test_store_accepts_system_default_category(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $systemCategory = Category::factory()->create(['family_id' => null, 'is_system' => true]);
        $systemPaymentMethod = PaymentMethod::factory()->create(['family_id' => null, 'is_system' => true]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.expenses.store'), [
                'amount' => '1500',
                'category_id' => $systemCategory->id,
                'payment_method_id' => $systemPaymentMethod->id,
                'expense_date' => '2026-06-20',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', [
            'family_id' => $family->id,
            'category_id' => $systemCategory->id,
            'payment_method_id' => $systemPaymentMethod->id,
        ]);
    }
}

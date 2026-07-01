<?php

namespace Tests\Feature\Budget;

use App\Models\Category;
use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\QuickEntry;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuickEntryControllerTest extends TestCase
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
            ->get(route('budget.quick-entries.index'))
            ->assertOk();
    }

    public function test_store_creates_quick_entry(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $shop = Shop::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.quick-entries.store'), [
                'name' => 'コンビニ',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'shop_id' => $shop->id,
                'default_amount' => '500',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('quick_entries', [
            'family_id' => $family->id,
            'name' => 'コンビニ',
            'category_id' => $category->id,
            'payment_method_id' => $pm->id,
            'shop_id' => $shop->id,
            'usage_count' => 0,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.quick-entries.store'), [
                'name' => '',
            ])->assertSessionHasErrors(['name', 'category_id', 'payment_method_id']);
    }

    public function test_store_rejects_category_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreignCategory = Category::factory()->create(['family_id' => $otherFamily->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.quick-entries.store'), [
                'name' => 'NG',
                'category_id' => $foreignCategory->id,
                'payment_method_id' => $pm->id,
            ])->assertSessionHasErrors('category_id');
    }

    public function test_store_rejects_shop_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreignShop = Shop::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.quick-entries.store'), [
                'name' => 'NG',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
                'shop_id' => $foreignShop->id,
            ])->assertSessionHasErrors('shop_id');
    }

    public function test_store_accepts_system_payment_method(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $systemPm = PaymentMethod::factory()->system()->create();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.quick-entries.store'), [
                'name' => '現金支出',
                'category_id' => $category->id,
                'payment_method_id' => $systemPm->id,
            ])->assertSessionHasNoErrors();
    }

    public function test_update_modifies_own_quick_entry(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $quickEntry = QuickEntry::factory()->create([
            'family_id' => $family->id,
            'category_id' => $category->id,
            'payment_method_id' => $pm->id,
            'name' => '旧名',
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.quick-entries.update', $quickEntry->id), [
                'name' => '新名',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
            ])->assertSessionHasNoErrors();

        $this->assertSame('新名', $quickEntry->fresh()->name);
    }

    public function test_update_cannot_touch_another_family_quick_entry(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);
        $foreign = QuickEntry::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.quick-entries.update', $foreign->id), [
                'name' => '改ざん',
                'category_id' => $category->id,
                'payment_method_id' => $pm->id,
            ])->assertNotFound();
    }

    public function test_destroy_removes_own_quick_entry(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $quickEntry = QuickEntry::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.quick-entries.destroy', $quickEntry->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('quick_entries', ['id' => $quickEntry->id]);
    }

    public function test_destroy_cannot_touch_another_family_quick_entry(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = QuickEntry::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.quick-entries.destroy', $foreign->id))
            ->assertNotFound();

        $this->assertDatabaseHas('quick_entries', ['id' => $foreign->id]);
    }

    public function test_use_increments_usage_count(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $quickEntry = QuickEntry::factory()->create(['family_id' => $family->id, 'usage_count' => 3]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->postJson(route('budget.quick-entries.use', $quickEntry->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(4, $quickEntry->fresh()->usage_count);
    }

    public function test_use_cannot_touch_another_family_quick_entry(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = QuickEntry::factory()->create(['family_id' => $otherFamily->id, 'usage_count' => 0]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->postJson(route('budget.quick-entries.use', $foreign->id))
            ->assertNotFound();

        $this->assertSame(0, $foreign->fresh()->usage_count);
    }

    public function test_index_orders_by_usage_count_desc(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        QuickEntry::factory()->create(['family_id' => $family->id, 'name' => '低頻度', 'usage_count' => 1]);
        QuickEntry::factory()->create(['family_id' => $family->id, 'name' => '高頻度', 'usage_count' => 9]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->get(route('budget.quick-entries.index'))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Budget/QuickEntries')
            ->where('quickEntries.0.name', '高頻度')
            ->where('quickEntries.1.name', '低頻度'));
    }
}

<?php

namespace Tests\Feature\Budget;

use App\Models\Category;
use App\Models\Family;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopControllerTest extends TestCase
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
            ->get(route('budget.shops.index'))
            ->assertOk();
    }

    public function test_store_creates_shop(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.shops.store'), [
                'name' => 'スーパーA',
                'default_category_id' => $category->id,
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('shops', [
            'family_id' => $family->id,
            'name' => 'スーパーA',
            'default_category_id' => $category->id,
            'usage_count' => 0,
        ]);
    }

    public function test_store_rejects_duplicate_name_in_same_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        Shop::factory()->create(['family_id' => $family->id, 'name' => 'スーパーA']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.shops.store'), [
                'name' => 'スーパーA',
            ])->assertSessionHasErrors('name');

        $this->assertSame(1, Shop::where('family_id', $family->id)->count());
    }

    public function test_store_allows_same_name_in_different_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        Shop::factory()->create(['family_id' => $otherFamily->id, 'name' => 'スーパーA']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.shops.store'), [
                'name' => 'スーパーA',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shops', ['family_id' => $family->id, 'name' => 'スーパーA']);
    }

    public function test_store_rejects_default_category_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreignCategory = Category::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.shops.store'), [
                'name' => 'スーパーB',
                'default_category_id' => $foreignCategory->id,
            ])->assertSessionHasErrors('default_category_id');
    }

    public function test_update_modifies_own_shop(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $shop = Shop::factory()->create(['family_id' => $family->id, 'name' => '旧店舗']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.shops.update', $shop->id), [
                'name' => '新店舗',
            ])->assertSessionHasNoErrors();

        $this->assertSame('新店舗', $shop->fresh()->name);
    }

    public function test_update_allows_keeping_same_name(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $shop = Shop::factory()->create(['family_id' => $family->id, 'name' => '据え置き']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.shops.update', $shop->id), [
                'name' => '据え置き',
            ])->assertSessionHasNoErrors();
    }

    public function test_update_cannot_touch_another_family_shop(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = Shop::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.shops.update', $foreign->id), [
                'name' => '改ざん',
            ])->assertNotFound();
    }

    public function test_destroy_removes_own_shop(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $shop = Shop::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.shops.destroy', $shop->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('shops', ['id' => $shop->id]);
    }

    public function test_destroy_cannot_touch_another_family_shop(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = Shop::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.shops.destroy', $foreign->id))
            ->assertNotFound();

        $this->assertDatabaseHas('shops', ['id' => $foreign->id]);
    }

    public function test_search_returns_family_shops_ordered_by_usage(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        Shop::factory()->create(['family_id' => $family->id, 'name' => 'スーパー低頻度', 'usage_count' => 1]);
        Shop::factory()->create(['family_id' => $family->id, 'name' => 'スーパー高頻度', 'usage_count' => 9]);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->getJson(route('budget.shops.search', ['q' => 'スーパー']));

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data);
        $this->assertSame('スーパー高頻度', $data[0]['name']);
        $this->assertSame('スーパー低頻度', $data[1]['name']);
    }

    public function test_search_excludes_other_family_shops(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        Shop::factory()->create(['family_id' => $otherFamily->id, 'name' => 'よその店']);

        $response = $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->getJson(route('budget.shops.search', ['q' => 'よそ']));

        $response->assertOk();
        $this->assertCount(0, $response->json());
    }
}

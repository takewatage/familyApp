<?php

namespace Tests\Feature\Budget;

use App\Models\Category;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
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
            ->get(route('budget.categories.index'))
            ->assertOk();
    }

    public function test_store_creates_category_with_incremented_sort_order(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        Category::factory()->create(['family_id' => $family->id, 'sort_order' => 5]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '食費',
                'color' => '#FF8800',
                'icon' => 'food',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'family_id' => $family->id,
            'name' => '食費',
            'color' => '#FF8800',
            'icon' => 'food',
            'is_system' => false,
            'is_active' => true,
            'sort_order' => 6,
        ]);
    }

    public function test_store_rejects_invalid_color(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '食費',
                'color' => 'red',
            ])->assertSessionHasErrors('color');

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_store_accepts_top_level_parent(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $parent = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '外食',
                'color' => '#112233',
                'parent_id' => $parent->id,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => '外食', 'parent_id' => $parent->id]);
    }

    public function test_store_rejects_parent_of_another_family(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreignParent = Category::factory()->create(['family_id' => $otherFamily->id, 'parent_id' => null]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '外食',
                'color' => '#112233',
                'parent_id' => $foreignParent->id,
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_store_rejects_non_top_level_parent(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $grandparent = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);
        $parent = Category::factory()->create(['family_id' => $family->id, 'parent_id' => $grandparent->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '孫',
                'color' => '#112233',
                'parent_id' => $parent->id,
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_store_rejects_inactive_parent(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $deletedParent = Category::factory()->create([
            'family_id' => $family->id,
            'parent_id' => null,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '孤児候補',
                'color' => '#112233',
                'parent_id' => $deletedParent->id,
            ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('categories', ['name' => '孤児候補']);
    }

    public function test_store_rejects_system_default_parent(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $systemParent = Category::factory()->create([
            'family_id' => null,
            'is_system' => true,
            'parent_id' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.categories.store'), [
                'name' => '孤児候補',
                'color' => '#112233',
                'parent_id' => $systemParent->id,
            ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('categories', ['name' => '孤児候補']);
    }

    public function test_update_rejects_reparenting_category_with_inactive_children(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $top = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);
        $parent = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);
        // 子は論理削除済み
        Category::factory()->create([
            'family_id' => $family->id,
            'parent_id' => $parent->id,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.categories.update', $parent->id), [
                'name' => '親を子に',
                'color' => '#00AA00',
                'parent_id' => $top->id,
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_update_modifies_own_category(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id, 'name' => '旧名']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.categories.update', $category->id), [
                'name' => '新名',
                'color' => '#00AA00',
            ])->assertSessionHasNoErrors();

        $this->assertSame('新名', $category->fresh()->name);
        $this->assertSame('#00AA00', $category->fresh()->color);
    }

    public function test_update_cannot_touch_system_default(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $system = Category::factory()->create(['family_id' => null, 'is_system' => true, 'name' => 'システム']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.categories.update', $system->id), [
                'name' => '改ざん',
                'color' => '#00AA00',
            ])->assertNotFound();

        $this->assertSame('システム', $system->fresh()->name);
    }

    public function test_update_cannot_touch_another_family_category(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = Category::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.categories.update', $foreign->id), [
                'name' => '改ざん',
                'color' => '#00AA00',
            ])->assertNotFound();
    }

    public function test_update_rejects_self_as_parent(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $category = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.categories.update', $category->id), [
                'name' => '自己親',
                'color' => '#00AA00',
                'parent_id' => $category->id,
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_update_rejects_parenting_category_with_children(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $top = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);
        $parent = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);
        Category::factory()->create(['family_id' => $family->id, 'parent_id' => $parent->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.categories.update', $parent->id), [
                'name' => '親を子に',
                'color' => '#00AA00',
                'parent_id' => $top->id,
            ])->assertSessionHasErrors('parent_id');
    }

    public function test_destroy_logically_deletes_with_children(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $parent = Category::factory()->create(['family_id' => $family->id, 'parent_id' => null]);
        $child = Category::factory()->create(['family_id' => $family->id, 'parent_id' => $parent->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.categories.destroy', $parent->id))
            ->assertRedirect();

        $this->assertFalse($parent->fresh()->is_active);
        $this->assertFalse($child->fresh()->is_active);
        // 物理削除はしない（支出の参照整合性を保つ）
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_destroy_cannot_touch_system_default(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $system = Category::factory()->create(['family_id' => null, 'is_system' => true]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.categories.destroy', $system->id))
            ->assertNotFound();

        $this->assertTrue($system->fresh()->is_active);
    }

    public function test_reorder_updates_sort_order_for_family_categories(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $a = Category::factory()->create(['family_id' => $family->id, 'sort_order' => 1]);
        $b = Category::factory()->create(['family_id' => $family->id, 'sort_order' => 2]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->postJson(route('budget.categories.reorder'), [
                'categories' => [
                    ['id' => $a->id, 'sort' => 10],
                    ['id' => $b->id, 'sort' => 5],
                ],
            ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame(10, (int) $a->fresh()->sort_order);
        $this->assertSame(5, (int) $b->fresh()->sort_order);
    }

    public function test_reorder_rejects_foreign_category(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = Category::factory()->create(['family_id' => $otherFamily->id, 'sort_order' => 1]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->postJson(route('budget.categories.reorder'), [
                'categories' => [
                    ['id' => $foreign->id, 'sort' => 9],
                ],
            ])->assertStatus(422);

        $this->assertSame(1, (int) $foreign->fresh()->sort_order);
    }
}

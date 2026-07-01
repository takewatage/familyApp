<?php

namespace Tests\Feature\Budget;

use App\Models\Family;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentMethodControllerTest extends TestCase
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
            ->get(route('budget.payment-methods.index'))
            ->assertOk();
    }

    public function test_store_creates_payment_method(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.payment-methods.store'), [
                'name' => 'QR決済',
                'icon' => 'qrcode',
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('payment_methods', [
            'family_id' => $family->id,
            'name' => 'QR決済',
            'icon' => 'qrcode',
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    public function test_store_sort_order_follows_system_defaults(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        // システム既定（sort_order 0-4）を模したデータ。家族行のみで max を取ると衝突する。
        PaymentMethod::factory()->system()->create(['sort_order' => 4]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.payment-methods.store'), [
                'name' => 'PayPay',
            ])->assertSessionHasNoErrors();

        // システム既定を含めた最大値(4)の次(5)になり、既定の間に割り込まない
        $this->assertDatabaseHas('payment_methods', [
            'family_id' => $family->id,
            'name' => 'PayPay',
            'sort_order' => 5,
        ]);
    }

    public function test_store_requires_name(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->post(route('budget.payment-methods.store'), [
                'name' => '',
            ])->assertSessionHasErrors('name');
    }

    public function test_update_modifies_own_payment_method(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id, 'name' => '旧名']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.payment-methods.update', $pm->id), [
                'name' => '新名',
            ])->assertSessionHasNoErrors();

        $this->assertSame('新名', $pm->fresh()->name);
    }

    public function test_update_cannot_touch_system_default(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $system = PaymentMethod::factory()->system()->create(['name' => '現金']);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.payment-methods.update', $system->id), [
                'name' => '改ざん',
            ])->assertNotFound();

        $this->assertSame('現金', $system->fresh()->name);
    }

    public function test_update_cannot_touch_another_family_payment_method(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        ['family' => $otherFamily] = $this->makeFamilyContext();
        $foreign = PaymentMethod::factory()->create(['family_id' => $otherFamily->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->patch(route('budget.payment-methods.update', $foreign->id), [
                'name' => '改ざん',
            ])->assertNotFound();
    }

    public function test_destroy_logically_deletes_own_payment_method(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $pm = PaymentMethod::factory()->create(['family_id' => $family->id]);

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.payment-methods.destroy', $pm->id))
            ->assertRedirect();

        // 論理削除: レコードは残り is_active=false になる
        $this->assertDatabaseHas('payment_methods', ['id' => $pm->id, 'is_active' => false]);
    }

    public function test_destroy_cannot_touch_system_default(): void
    {
        ['user' => $user, 'family' => $family] = $this->makeFamilyContext();
        $system = PaymentMethod::factory()->system()->create();

        $this->actingAs($user)
            ->withSession(['current_family_id' => $family->id])
            ->delete(route('budget.payment-methods.destroy', $system->id))
            ->assertNotFound();

        $this->assertDatabaseHas('payment_methods', ['id' => $system->id, 'is_active' => true]);
    }
}

<?php

namespace App\Services;

use App\Models\RecurringExpense;
use App\Services\Concerns\ValidatesFamilyMember;

/**
 * 繰り返し支出（固定費）の作成・更新を担う。
 *
 * カテゴリー・支払い方法・店舗の家族スコープは StoreRecurringExpenseRequest の Rule::exists で
 * 担保されるため、ここでは exists では検証できない担当者（polymorphic member）の家族スコープのみ
 * ValidatesFamilyMember で追加検証する。
 */
class RecurringExpenseService
{
    use ValidatesFamilyMember;

    /**
     * 繰り返し支出を作成する。
     *
     * @param  array<string, mixed>  $attributes  family スコープ済みの属性
     */
    public function create(string $familyId, array $attributes): RecurringExpense
    {
        $this->assertMemberInFamilyScope($familyId, $attributes);

        return RecurringExpense::create(
            ['family_id' => $familyId, 'is_active' => true, 'last_generated_date' => null]
            + $this->buildPersistedAttributes($attributes)
        );
    }

    /**
     * 繰り返し支出を更新する。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(RecurringExpense $recurring, array $attributes): RecurringExpense
    {
        $this->assertMemberInFamilyScope($recurring->family_id, $attributes);

        $recurring->update($this->buildPersistedAttributes($attributes));

        return $recurring;
    }

    /**
     * 永続化する属性を組み立てる（create / update 共通。family_id・is_active は呼び出し側）。
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function buildPersistedAttributes(array $attributes): array
    {
        return [
            'member_type' => $attributes['member_type'] ?? null,
            'member_id' => $attributes['member_id'] ?? null,
            'category_id' => $attributes['category_id'],
            'payment_method_id' => $attributes['payment_method_id'],
            'shop_id' => $attributes['shop_id'] ?? null,
            'name' => $attributes['name'],
            'amount' => $attributes['amount'],
            'day_of_month' => $attributes['day_of_month'],
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'] ?? null,
        ];
    }
}

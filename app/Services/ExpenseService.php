<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Shop;
use App\Models\User;
use App\Models\VirtualUser;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    /**
     * 月の支出一覧を関連込みで取得する（日付降順 → 作成日時降順）。
     */
    public function listForMonth(string $familyId, string $yearMonth)
    {
        $start = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // whereYear/whereMonth は expense_date を関数で包み (family_id, expense_date) 複合
        // インデックスのレンジ探索を無効化するため、範囲条件で直接シークさせる。
        return Expense::with(['category', 'paymentMethod', 'shop', 'member'])
            ->where('family_id', $familyId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * 支出を作成する。店舗の解決（既存紐付け or 手入力保持）と利用回数加算を行う。
     *
     * @param  array<string, mixed>  $attributes  family スコープ済みの属性
     */
    public function create(string $familyId, array $attributes): Expense
    {
        $this->assertReferencesInFamilyScope($familyId, $attributes);
        $shop = $this->resolveShop($familyId, $attributes['shop_id'] ?? null, $attributes['shop_name'] ?? null);

        $expense = Expense::create(
            ['family_id' => $familyId] + $this->buildPersistedAttributes($attributes, $shop)
        );

        $this->adjustShopUsage(null, $shop['shop_id']);

        return $expense;
    }

    /**
     * 支出を更新する。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Expense $expense, array $attributes): Expense
    {
        $this->assertReferencesInFamilyScope($expense->family_id, $attributes);
        $previousShopId = $expense->shop_id;
        $shop = $this->resolveShop($expense->family_id, $attributes['shop_id'] ?? null, $attributes['shop_name'] ?? null);

        $expense->update($this->buildPersistedAttributes($attributes, $shop));

        $this->adjustShopUsage($previousShopId, $shop['shop_id']);

        return $expense;
    }

    /**
     * 支出を削除する。紐付く店舗の利用回数を減算する。
     */
    public function delete(Expense $expense): void
    {
        $shopId = $expense->shop_id;

        $expense->delete();

        $this->adjustShopUsage($shopId, null);
    }

    /**
     * 永続化する支出属性を組み立てる（create / update 共通。family_id は呼び出し側で付与）。
     *
     * @param  array<string, mixed>  $attributes
     * @param  array{shop_id: ?string, shop_name: ?string}  $shop
     * @return array<string, mixed>
     */
    private function buildPersistedAttributes(array $attributes, array $shop): array
    {
        return [
            'member_type' => $attributes['member_type'] ?? null,
            'member_id' => $attributes['member_id'] ?? null,
            'category_id' => $attributes['category_id'],
            'payment_method_id' => $attributes['payment_method_id'],
            'shop_id' => $shop['shop_id'],
            'shop_name' => $shop['shop_name'],
            'amount' => $attributes['amount'],
            'expense_date' => $attributes['expense_date'],
            'memo' => $attributes['memo'] ?? null,
        ];
    }

    /**
     * 外部キー（カテゴリー・支払い方法・担当者）が当該家族のスコープ内であることを保証する。
     *
     * バリデーションの exists ルールはグローバル存在のみを見るため、他家族の非公開
     * カテゴリー / 支払い方法 / メンバーの ID を含むリクエストが素通りしてしまう。
     * 一覧描画時に他家族の名称・色・担当者名が露出する越境漏洩を、ここで 422 として塞ぐ。
     *
     * @param  array<string, mixed>  $attributes
     */
    private function assertReferencesInFamilyScope(string $familyId, array $attributes): void
    {
        $categoryVisible = Category::query()
            ->whereKey($attributes['category_id'])
            ->visibleTo($familyId)
            ->exists();

        if (! $categoryVisible) {
            throw ValidationException::withMessages(['category_id' => 'カテゴリーが選択できません。']);
        }

        $paymentVisible = PaymentMethod::query()
            ->whereKey($attributes['payment_method_id'])
            ->visibleTo($familyId)
            ->exists();

        if (! $paymentVisible) {
            throw ValidationException::withMessages(['payment_method_id' => '支払い方法が選択できません。']);
        }

        $memberType = $attributes['member_type'] ?? null;
        $memberId = $attributes['member_id'] ?? null;

        if ($memberType !== null && $memberId !== null
            && ! $this->memberBelongsToFamily($familyId, $memberType, $memberId)) {
            throw ValidationException::withMessages(['member_id' => '担当者が選択できません。']);
        }
    }

    /**
     * 担当者（実ユーザー / 仮想ユーザー）が当該家族に所属しているか。
     */
    private function memberBelongsToFamily(string $familyId, string $memberType, string $memberId): bool
    {
        return match ($memberType) {
            User::class => User::whereKey($memberId)
                ->whereHas('families', fn ($q) => $q->whereKey($familyId))
                ->exists(),
            VirtualUser::class => VirtualUser::whereKey($memberId)
                ->where('family_id', $familyId)
                ->exists(),
            default => false,
        };
    }

    /**
     * 店舗を解決する（利用回数の増減は呼び出し側の adjustShopUsage が担う）。
     * - shop_id 指定あり → family スコープで紐付け（shop_name は破棄）
     * - shop_id なし & shop_name あり → 同名店舗が既存なら紐付け、なければ手入力テキストとして保持
     *
     * @return array{shop_id: ?string, shop_name: ?string}
     */
    private function resolveShop(string $familyId, ?string $shopId, ?string $shopName): array
    {
        if ($shopId) {
            $shop = Shop::where('family_id', $familyId)->find($shopId);

            // 他家族の店舗 ID（バリデーションの exists は family を見ない）は無言で落とさず弾く。
            if (! $shop) {
                throw ValidationException::withMessages(['shop_id' => '店舗が選択できません。']);
            }

            return ['shop_id' => $shop->id, 'shop_name' => null];
        }

        $name = $shopName !== null ? trim($shopName) : '';

        if ($name === '') {
            return ['shop_id' => null, 'shop_name' => null];
        }

        $existing = Shop::where('family_id', $familyId)->where('name', $name)->first();

        if ($existing) {
            return ['shop_id' => $existing->id, 'shop_name' => null];
        }

        // 未登録の手入力店名はテキストとして保持する（店舗登録は店舗管理 / T-5 で明示的に行う）
        return ['shop_id' => null, 'shop_name' => $name];
    }

    /**
     * 店舗の利用回数を「紐付く支出数」として増減する。
     * usage_count は支出が紐付くたびに +1、外れるたびに −1 で、店舗候補の並び順（利用頻度）に使う。
     * 同一店舗（変更なし）は何もしない。減算は負値防止のため usage_count > 0 のときのみ。
     */
    private function adjustShopUsage(?string $oldShopId, ?string $newShopId): void
    {
        if ($oldShopId === $newShopId) {
            return;
        }

        if ($oldShopId !== null) {
            Shop::where('id', $oldShopId)->where('usage_count', '>', 0)->decrement('usage_count');
        }

        if ($newShopId !== null) {
            Shop::where('id', $newShopId)->increment('usage_count');
        }
    }
}

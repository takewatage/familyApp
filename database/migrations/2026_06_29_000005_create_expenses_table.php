<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->comment('支出|支出記録を管理するメインテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->nullableUuidMorphs('member'); // member_type + member_id（支払った担当者: User / VirtualUser・任意）
            $table->foreignUuid('category_id')->comment('カテゴリーID')->constrained('categories');
            $table->foreignUuid('payment_method_id')->comment('支払い方法ID')->constrained('payment_methods');
            $table->foreignUuid('shop_id')->nullable()->comment('店舗ID')->constrained('shops')->nullOnDelete();
            $table->decimal('amount', 12, 2)->comment('金額（円）');
            $table->string('shop_name', 100)->nullable()->comment('店舗名（shops未登録の手入力時）');
            $table->date('expense_date')->comment('支出日');
            $table->text('memo')->nullable()->comment('メモ');
            $table->boolean('is_recurring')->default(false)->comment('固定費（繰り返し）フラグ');
            $table->foreignUuid('recurring_expense_id')->nullable()->comment('元となる繰り返し支出ID')->constrained('recurring_expenses')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'expense_date']);
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

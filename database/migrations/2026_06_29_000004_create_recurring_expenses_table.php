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
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->comment('繰り返し支出|固定費などの繰り返し支出を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->nullableUuidMorphs('member'); // member_type + member_id（担当者: User / VirtualUser・任意）
            $table->foreignUuid('category_id')->comment('カテゴリーID')->constrained('categories');
            $table->foreignUuid('payment_method_id')->comment('支払い方法ID')->constrained('payment_methods');
            $table->foreignUuid('shop_id')->nullable()->comment('店舗ID')->constrained('shops')->nullOnDelete();
            $table->string('name', 100)->comment('支出名（例：家賃、電気代）');
            $table->decimal('amount', 12, 2)->comment('金額（円）');
            $table->unsignedTinyInteger('day_of_month')->comment('支払日（1-31）');
            $table->date('start_date')->comment('開始日');
            $table->date('end_date')->nullable()->comment('終了日（NULL=無期限）');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->date('last_generated_date')->nullable()->comment('最後に支出を生成した年月');
            $table->timestamps();

            $table->index(['family_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};

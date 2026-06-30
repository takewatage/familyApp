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
        Schema::create('quick_entries', function (Blueprint $table) {
            $table->comment('クイック入力|よく使う支出パターンを保存するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->nullableUuidMorphs('member'); // member_type + member_id（作成者: User / VirtualUser・任意）
            $table->string('name', 50)->comment('クイック入力名（例：コンビニ昼食）');
            $table->foreignUuid('category_id')->comment('カテゴリーID')->constrained('categories');
            $table->foreignUuid('payment_method_id')->comment('支払い方法ID')->constrained('payment_methods');
            $table->foreignUuid('shop_id')->nullable()->comment('店舗ID')->constrained('shops')->nullOnDelete();
            $table->decimal('default_amount', 12, 2)->nullable()->comment('デフォルト金額（NULL=毎回入力）');
            $table->integer('sort_order')->default(0)->comment('表示順');
            $table->unsignedInteger('usage_count')->default(0)->comment('利用回数');
            $table->timestamps();

            $table->index(['family_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_entries');
    }
};

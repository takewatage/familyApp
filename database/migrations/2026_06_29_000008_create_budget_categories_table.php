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
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->comment('カテゴリー別予算|カテゴリーごとの予算配分を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->comment('予算ID')->constrained('budgets')->cascadeOnDelete();
            $table->foreignUuid('category_id')->comment('カテゴリーID')->constrained('categories')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0)->comment('予算額');
            $table->timestamps();

            $table->unique(['budget_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_categories');
    }
};

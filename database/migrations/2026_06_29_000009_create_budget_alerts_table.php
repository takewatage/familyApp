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
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->comment('予算アラート|予算消化時のアラート設定を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->comment('カテゴリーID（NULL=全体）')->constrained('categories')->cascadeOnDelete();
            $table->unsignedTinyInteger('threshold_percent')->default(80)->comment('アラート閾値（%）');
            $table->boolean('is_enabled')->default(true)->comment('有効フラグ');
            $table->timestamps();

            $table->index('family_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_alerts');
    }
};

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
        Schema::create('shops', function (Blueprint $table) {
            $table->comment('店舗|よく利用する店舗を管理するテーブル（オートコンプリート用）');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->string('name', 100)->comment('店舗名');
            $table->foreignUuid('default_category_id')->nullable()->comment('デフォルトカテゴリーID')->constrained('categories')->nullOnDelete();
            $table->unsignedInteger('usage_count')->default(0)->comment('利用回数（候補順序付け用）');
            $table->timestamps();

            $table->unique(['family_id', 'name']);
            $table->index(['family_id', 'usage_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};

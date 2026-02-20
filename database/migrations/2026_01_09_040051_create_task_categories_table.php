<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_categories', function (Blueprint $table) {
            $table->comment('タスクカテゴリー|タスクのカテゴリー情報を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families');
            $table->string('name')->comment('カテゴリー名');
            $table->string('color')->comment('色');
            $table->integer('sort')->default(0)->comment('並び順');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_categories');
    }
};

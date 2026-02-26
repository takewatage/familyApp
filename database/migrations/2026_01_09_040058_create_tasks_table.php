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
        Schema::create('tasks', function (Blueprint $table) {
            $table->comment('タスク|タスク情報を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families');
            $table->foreignUuid('category_id')->comment('カテゴリーID')->constrained('task_categories');
            $table->string('content')->comment('内容');
            $table->string('color')->nullable()->comment('色');
            $table->text('memo')->nullable()->comment('メモ');
            $table->boolean('is_completed')->default(false)->comment('完了');
            $table->timestamp('completed_at')->nullable()->comment('完了日');
            $table->integer('sort')->default(0)->comment('並び順');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

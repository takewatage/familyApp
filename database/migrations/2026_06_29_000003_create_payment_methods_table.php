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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->comment('支払い方法|支払い方法を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->nullable()->comment('家族ID（NULL=システムデフォルト）')->constrained('families')->cascadeOnDelete();
            $table->string('name', 50)->comment('支払い方法名');
            $table->string('icon', 50)->nullable()->comment('アイコン識別子');
            $table->integer('sort_order')->default(0)->comment('表示順（昇順）');
            $table->boolean('is_system')->default(false)->comment('システムデフォルトフラグ');
            $table->boolean('is_active')->default(true)->comment('有効フラグ（論理削除用）');
            $table->timestamps();

            $table->index(['family_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};

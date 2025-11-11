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
        Schema::create('family_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->onDelete('cascade');
            $table->foreignUuid('user_id')->comment('ユーザーID')->constrained('users')->onDelete('cascade');
            $table
                ->enum('role', ['owner', 'parent', 'child', 'guest'])
                ->default('guest')
                ->comment('家族内権限');
            $table->timestamps();

            // 同じユーザーが同じ家族に複数回参加できないようにユニーク制約
            $table->unique(['family_id', 'user_id']);

            // インデックス
            $table->index('family_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_user');
    }
};

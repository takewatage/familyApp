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
        Schema::create('families', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('家族名');
            $table->string('code', 10)->unique()->comment('家族コード（招待用）');
            $table->foreignUuid('owner_id')->comment('家族ID')->constrained('users');
            $table->integer('max_members')->default(10)->comment('最大メンバー数');
            $table->json('settings')->nullable()->comment('設定');
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};

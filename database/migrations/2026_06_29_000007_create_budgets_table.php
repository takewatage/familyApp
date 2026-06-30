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
        Schema::create('budgets', function (Blueprint $table) {
            $table->comment('予算|月次予算を管理するテーブル');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->char('year_month', 7)->comment('対象年月（YYYY-MM形式）');
            $table->decimal('total_income', 12, 2)->default(0)->comment('月収入');
            $table->decimal('saving_target', 12, 2)->default(0)->comment('貯金目標額');
            $table->timestamps();

            $table->unique(['family_id', 'year_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};

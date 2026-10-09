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
        Schema::create('calendar_labels', function (Blueprint $table) {
            $table->comment('カレンダーのラベル|予定に付ける名前とカラー（家族で共有）');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->string('name', 20)->comment('ラベル名');
            $table->char('color', 7)->comment('カラー（#rrggbb）');
            $table->unsignedSmallInteger('sort')->default(0)->comment('並び順');
            $table->timestamps();

            $table->index(['family_id', 'sort']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_labels');
    }
};

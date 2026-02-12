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
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->constrained('families');
            $table->string('name');
            $table->string('color');
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

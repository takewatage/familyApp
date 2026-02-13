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
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->constrained('families');
            $table->string('content')->comment('内容');
            $table->foreignUuid('category_id')->constrained('task_categories');
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

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
        Schema::create('calendar_event_exdates', function (Blueprint $table) {
            $table->comment('繰り返し予定の除外日|「この回だけ削除」した発生日（RRULE の EXDATE）');
            $table->id();
            $table->foreignUuid('calendar_event_id')->comment('繰り返し予定ID')->constrained('calendar_events')->cascadeOnDelete();
            $table->date('date')->comment('除外する発生日');
            $table->timestamps();

            $table->unique(['calendar_event_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_event_exdates');
    }
};

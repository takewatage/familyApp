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
        Schema::create('calendar_event_participants', function (Blueprint $table) {
            $table->comment('予定の参加者|予定に関係する家族メンバー（User / VirtualUser）');
            $table->id();
            $table->foreignUuid('calendar_event_id')->comment('予定ID')->constrained('calendar_events')->cascadeOnDelete();
            // participant_type + participant_id（User / VirtualUser）。既定のインデックス名は MySQL の上限（64 文字）を超えるため短くする
            $table->uuidMorphs('participant', 'calendar_participant_morph_index');
            $table->timestamps();

            $table->unique(['calendar_event_id', 'participant_type', 'participant_id'], 'calendar_event_participants_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_event_participants');
    }
};

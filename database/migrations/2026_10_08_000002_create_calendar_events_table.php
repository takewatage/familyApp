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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->comment('カレンダーの予定|家族で共有する予定。繰り返しは RRULE で持ち、この回だけの変更は上書き予定として持つ');
            $table->uuid('id')->primary();
            $table->foreignUuid('family_id')->comment('家族ID')->constrained('families')->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->comment('作成者')->constrained('users')->nullOnDelete();
            $table->string('title', 100)->comment('タイトル');
            $table->text('memo')->nullable()->comment('メモ');
            $table->foreignUuid('label_id')->nullable()->comment('ラベルID')->constrained('calendar_labels')->nullOnDelete();
            $table->boolean('all_day')->default(true)->comment('終日の予定か');
            $table->date('start_date')->comment('開始日（現地日付）');
            $table->date('end_date')->comment('終了日（現地日付）');
            $table->time('start_time')->nullable()->comment('開始時刻（終日なら NULL）');
            $table->time('end_time')->nullable()->comment('終了時刻（任意・終日なら NULL）');
            $table->string('rrule', 255)->nullable()->comment('繰り返しルール（RFC 5545 の RRULE。DTSTART は start_date）');
            $table->date('recurrence_end_date')->nullable()->comment('繰り返しの最後の発生日（UNTIL / COUNT から計算。NULL=終わりなし）');
            $table->foreignUuid('recurring_event_id')->nullable()->comment('この回だけ変更した予定の繰り返し元')->constrained('calendar_events')->cascadeOnDelete();
            $table->date('original_date')->nullable()->comment('この回だけ変更した予定の、本来の発生日');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['family_id', 'start_date']);
            $table->index(['family_id', 'recurrence_end_date']);
            $table->unique(['recurring_event_id', 'original_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};

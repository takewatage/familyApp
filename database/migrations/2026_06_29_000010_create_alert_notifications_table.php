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
        Schema::create('alert_notifications', function (Blueprint $table) {
            $table->comment('アラート通知履歴|送信済みアラート通知を管理するテーブル（重複通知防止用）');
            $table->uuid('id')->primary();
            $table->foreignUuid('alert_id')->comment('アラートID')->constrained('budget_alerts')->cascadeOnDelete();
            $table->char('year_month', 7)->comment('対象年月（YYYY-MM形式）');
            $table->timestamp('triggered_at')->useCurrent()->comment('通知発生日時');
            $table->decimal('actual_percent', 5, 2)->comment('実際の消化率（%）');

            $table->unique(['alert_id', 'year_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_notifications');
    }
};

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
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('fileable');
            $table->string('collection', 50)->comment('用途の区別(カラム名など)');
            $table->string('path', 50)->comment('アクセスID');
            $table->string('url', 50)->comment('表示用URL');
            $table->string('name', 255)->comment('ファイル名');
            $table->string('mime_type', 50)->comment('MIMEタイプ');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['fileable_type', 'fileable_id', 'collection']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};

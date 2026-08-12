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
        Schema::table('users', function (Blueprint $table) {
            $table
                ->string('google_id')
                ->nullable()
                ->unique()
                ->after('email')
                ->comment('GoogleアカウントID（sub）');

            // Googleのみで登録したユーザーはパスワードを持たない
            $table
                ->string('password')
                ->nullable()
                ->comment('パスワード（ハッシュ）')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');

            $table
                ->string('password')
                ->nullable(false)
                ->comment('パスワード（ハッシュ）')
                ->change();
        });
    }
};

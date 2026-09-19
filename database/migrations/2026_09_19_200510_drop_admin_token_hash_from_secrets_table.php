<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->dropUnique(['admin_token_hash']);
            $table->dropColumn('admin_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->string('admin_token_hash', 64)->nullable()->unique()->after('token');
        });
    }
};

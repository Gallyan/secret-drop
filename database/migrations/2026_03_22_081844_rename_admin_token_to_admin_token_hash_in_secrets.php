<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Widen column first to fit SHA-256 hashes (64 hex chars)
        Schema::table('secrets', function (Blueprint $table) {
            $table->string('admin_token', 64)->change();
        });

        // Hash existing admin_tokens in place
        DB::table('secrets')->whereNotNull('admin_token')->orderBy('id')->chunk(100, function ($secrets) {
            foreach ($secrets as $secret) {
                DB::table('secrets')
                    ->where('id', $secret->id)
                    ->update(['admin_token' => hash('sha256', $secret->admin_token)]);
            }
        });

        // Rename column
        Schema::table('secrets', function (Blueprint $table) {
            $table->renameColumn('admin_token', 'admin_token_hash');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->renameColumn('creator_email', 'creator_email_hash');
        });
    }
};

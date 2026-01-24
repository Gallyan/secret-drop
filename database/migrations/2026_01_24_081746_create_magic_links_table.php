<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('magic_links', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('secret_id');
            $table->foreign('secret_id')
                ->references('id')
                ->on('secrets')
                ->cascadeOnDelete();

            $table->string('email');
            $table->string('token_hash', 64)->unique();

            $table->timestamp('expire_at')->index();
            $table->timestamp('used_at')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['secret_id', 'email']);
        });
    }
};

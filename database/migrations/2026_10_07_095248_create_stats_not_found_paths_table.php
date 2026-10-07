<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_not_found_paths', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('path', 150);
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_not_found_paths');
    }
};

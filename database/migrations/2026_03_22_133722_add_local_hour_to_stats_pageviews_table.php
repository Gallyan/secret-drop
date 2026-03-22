<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_local_hours', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->tinyInteger('local_hour')->unsigned();
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'local_hour']);
            $table->index('date');
        });
    }
};

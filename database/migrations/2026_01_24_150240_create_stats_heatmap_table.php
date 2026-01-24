<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_heatmap', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('day_of_week')->unsigned();
            $table->tinyInteger('hour')->unsigned();
            $table->string('metric', 50);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['day_of_week', 'hour', 'metric']);
        });
    }
};

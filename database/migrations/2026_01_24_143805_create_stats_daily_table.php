<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('metric', 50);
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['date', 'metric']);
            $table->index('date');
            $table->index('metric');
        });
    }
};

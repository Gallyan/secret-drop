<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_pageviews', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('page', 50);
            $table->boolean('is_bot')->default(false);
            $table->tinyInteger('hour')->unsigned();
            $table->string('country', 2)->default('XX');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'page', 'is_bot', 'hour', 'country']);
            $table->index('date');
            $table->index(['page', 'date']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('stats_referrers', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('referrer_domain', 100);
            $table->boolean('is_bot')->default(false);
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'referrer_domain', 'is_bot']);
            $table->index('date');
        });
    }
};

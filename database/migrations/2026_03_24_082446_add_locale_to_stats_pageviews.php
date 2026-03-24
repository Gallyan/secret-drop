<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('stats_pageviews', function (Blueprint $table) {
            $table->string('locale', 5)->default('')->after('country');

            $table->dropUnique(['date', 'page', 'is_bot', 'hour', 'country']);
            $table->unique(['date', 'page', 'is_bot', 'hour', 'country', 'locale']);
        });
    }
};

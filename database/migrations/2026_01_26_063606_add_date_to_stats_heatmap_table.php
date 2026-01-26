<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Clear existing heatmap data (it was aggregated without dates, unusable for period filtering)
        DB::table('stats_heatmap')->truncate();

        Schema::table('stats_heatmap', function (Blueprint $table) {
            $table->dropUnique(['day_of_week', 'hour', 'metric']);
            $table->date('date')->after('id');
            $table->unique(['date', 'day_of_week', 'hour', 'metric']);
        });
    }
};

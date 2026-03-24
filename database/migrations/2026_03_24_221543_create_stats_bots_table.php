<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('stats_bots')) {
            Schema::create('stats_bots', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('bot_name', 50);
                $table->unsignedInteger('count')->default(1);
                $table->timestamps();

                $table->unique(['date', 'bot_name']);
                $table->index('date');
            });
        }

        DB::table('stats_pageviews')->where('page', 'like', 'generated::%')->delete();
    }
};

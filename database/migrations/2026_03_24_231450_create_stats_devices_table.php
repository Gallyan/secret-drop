<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('stats_devices')) {
            return;
        }

        Schema::create('stats_devices', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('device_type', 10);
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->unique(['date', 'device_type']);
            $table->index('date');
        });
    }
};

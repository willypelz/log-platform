<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_platform_metric_timeseries', function (Blueprint $table) {
            $table->id();
            $table->string('env', 32);
            $table->string('metric', 64);
            $table->dateTime('bucket_start');
            $table->integer('bucket_size');
            $table->double('value');

            $table->unique(['env', 'metric', 'bucket_start', 'bucket_size'], 'metric_unique');
            $table->index(['env', 'metric', 'bucket_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_platform_metric_timeseries');
    }
};


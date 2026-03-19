<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_platform_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->text('query');
            $table->integer('window_seconds')->default(60);
            $table->integer('threshold_count')->default(10);
            $table->json('channels');
            $table->integer('cooldown_seconds')->nullable();
            $table->timestamps();

            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_platform_alert_rules');
    }
};


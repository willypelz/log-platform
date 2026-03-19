<?php

};
    }
        Schema::dropIfExists('log_platform_alert_events');
    {
    public function down(): void

    }
        });
            $table->index(['alert_rule_id', 'triggered_at']);

            $table->timestamps();
            $table->json('delivery_status')->nullable();
            $table->json('payload');
            $table->integer('match_count');
            $table->dateTime('triggered_at');
            $table->foreignId('alert_rule_id')->constrained('log_platform_alert_rules')->onDelete('cascade');
            $table->id();
        Schema::create('log_platform_alert_events', function (Blueprint $table) {
    {
    public function up(): void
{
return new class extends Migration

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


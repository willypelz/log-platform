<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_platform_indexed_logs', function (Blueprint $table) {
            $table->id();
            $table->string('env', 32)->index();
            $table->string('channel', 64)->index();
            $table->string('level', 16)->index();
            $table->dateTime('logged_at')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('request_id', 64)->nullable()->index();
            $table->char('fingerprint', 64)->nullable()->index();
            $table->string('source_file', 255)->index();
            $table->unsignedBigInteger('source_offset')->index();

            // Composite indexes for common queries
            $table->index(['env', 'logged_at']);
            $table->index(['level', 'logged_at']);
            $table->index(['request_id', 'logged_at']);
            $table->index(['fingerprint', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_platform_indexed_logs');
    }
};


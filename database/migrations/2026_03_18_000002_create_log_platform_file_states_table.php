<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_platform_file_states', function (Blueprint $table) {
            $table->id();
            $table->string('env', 32);
            $table->string('channel', 64);
            $table->string('path', 255);
            $table->unsignedBigInteger('inode')->nullable();
            $table->unsignedBigInteger('last_offset')->default(0);
            $table->dateTime('last_seen_at')->nullable();
            $table->char('last_hash', 64)->nullable();
            $table->enum('status', ['active', 'rotated', 'missing'])->default('active');
            $table->timestamps();

            $table->unique(['path', 'env', 'channel']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_platform_file_states');
    }
};


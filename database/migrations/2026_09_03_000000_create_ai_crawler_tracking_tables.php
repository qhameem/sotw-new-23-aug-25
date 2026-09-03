<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_crawler_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('visited_on');
            $table->string('bot', 100);
            $table->string('path_hash', 64);
            $table->text('path');
            $table->unsignedSmallInteger('status_code');
            $table->unsignedBigInteger('requests')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['visited_on', 'bot', 'path_hash', 'status_code'], 'ai_crawler_daily_unique');
            $table->index(['bot', 'visited_on']);
        });

        Schema::create('ai_crawler_log_states', function (Blueprint $table) {
            $table->id();
            $table->string('path_hash', 64)->unique();
            $table->text('path');
            $table->string('inode', 100)->nullable();
            $table->unsignedBigInteger('byte_offset')->default(0);
            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_crawler_log_states');
        Schema::dropIfExists('ai_crawler_daily_stats');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_launch_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('launch_window_start');
            $table->timestamp('launch_window_end');
            $table->unsignedInteger('manual_upvotes')->default(0);
            $table->unsignedInteger('list_impressions')->default(0);
            $table->unsignedInteger('detail_views')->default(0);
            $table->unsignedInteger('outbound_clicks')->default(0);
            $table->decimal('exploration_score', 12, 4)->default(0);
            $table->timestamp('last_served_at')->nullable();
            $table->timestamps();

            $table->unique('product_id');
            $table->index(['launch_window_end', 'exploration_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_launch_stats');
    }
};

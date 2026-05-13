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
        Schema::create('product_week_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->unsignedInteger('manual_upvotes')->default(0);
            $table->unsignedInteger('list_impressions')->default(0);
            $table->unsignedInteger('detail_views')->default(0);
            $table->unsignedInteger('outbound_clicks')->default(0);
            $table->decimal('ranking_score', 12, 4)->default(0);
            $table->unsignedInteger('final_rank')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'week_start']);
            $table->index(['week_start', 'ranking_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_week_stats');
    }
};

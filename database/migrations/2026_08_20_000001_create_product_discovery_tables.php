<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_discovery_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('url');
            $table->string('type', 20)->default('auto');
            $table->string('item_selector')->nullable();
            $table->string('link_selector')->nullable();
            $table->string('title_selector')->nullable();
            $table->string('description_selector')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('max_items')->default(30);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('product_discovery_sources')->cascadeOnDelete();
            $table->string('title');
            $table->text('url');
            $table->string('url_hash', 64)->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('score')->default(50)->index();
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('discovered_at')->index();
            $table->timestamp('last_seen_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recommendations');
        Schema::dropIfExists('product_discovery_sources');
    }
};

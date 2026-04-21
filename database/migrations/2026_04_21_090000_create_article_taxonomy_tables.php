<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('article_categories')) {
            Schema::create('article_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained('article_categories')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('article_tags')) {
            Schema::create('article_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('article_category_pivot')) {
            Schema::create('article_category_pivot', function (Blueprint $table) {
                $table->id();
                $table->foreignId('article_id')->constrained()->cascadeOnDelete();
                $table->foreignId('article_category_id')->constrained('article_categories')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['article_id', 'article_category_id'], 'article_category_unique');
            });
        }

        if (!Schema::hasTable('article_tag_pivot')) {
            Schema::create('article_tag_pivot', function (Blueprint $table) {
                $table->id();
                $table->foreignId('article_id')->constrained()->cascadeOnDelete();
                $table->foreignId('article_tag_id')->constrained('article_tags')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['article_id', 'article_tag_id'], 'article_tag_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tag_pivot');
        Schema::dropIfExists('article_category_pivot');
        Schema::dropIfExists('article_tags');
        Schema::dropIfExists('article_categories');
    }
};

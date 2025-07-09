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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Assuming 'users' table exists
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->string('status')->default('draft'); // e.g., draft, published, scheduled
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable(); // Added
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable(); // Added
            $table->string('og_url')->nullable(); // Added
            $table->string('twitter_card')->nullable(); // Added (e.g., summary, summary_large_image)
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            // $table->string('twitter_image')->nullable(); // Consider adding if needed
            $table->string('featured_image_path')->nullable(); // For featured image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};

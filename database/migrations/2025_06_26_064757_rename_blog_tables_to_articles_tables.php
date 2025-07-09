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
        Schema::rename('blog_posts', 'articles');
        Schema::rename('blog_categories', 'article_categories');
        Schema::rename('blog_tags', 'article_tags');
        Schema::rename('blog_post_category_pivot', 'article_category_pivot');
        Schema::rename('blog_post_tag_pivot', 'article_tag_pivot');

        Schema::table('article_category_pivot', function (Blueprint $table) {
            $table->renameColumn('blog_post_id', 'article_id');
            $table->renameColumn('blog_category_id', 'article_category_id');
        });

        Schema::table('article_tag_pivot', function (Blueprint $table) {
            $table->renameColumn('blog_post_id', 'article_id');
            $table->renameColumn('blog_tag_id', 'article_tag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_tag_pivot', function (Blueprint $table) {
            $table->renameColumn('article_tag_id', 'blog_tag_id');
            $table->renameColumn('article_id', 'blog_post_id');
        });

        Schema::table('article_category_pivot', function (Blueprint $table) {
            $table->renameColumn('article_category_id', 'blog_category_id');
            $table->renameColumn('article_id', 'blog_post_id');
        });

        Schema::rename('article_tag_pivot', 'blog_post_tag_pivot');
        Schema::rename('article_category_pivot', 'blog_post_category_pivot');
        Schema::rename('article_tags', 'blog_tags');
        Schema::rename('article_categories', 'blog_categories');
        Schema::rename('articles', 'blog_posts');
    }
};

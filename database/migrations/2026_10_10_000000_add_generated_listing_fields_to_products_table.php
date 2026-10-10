<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('facts_json')->nullable();
            $table->text('summary')->nullable();
            $table->json('features')->nullable();
            $table->string('best_for_text')->nullable();
            $table->string('not_for_text')->nullable();
            $table->json('faq')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('generation_status', 20)->nullable()->index();
            $table->boolean('generation_noindex')->default(false);
            $table->boolean('generation_review_required')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['facts_json', 'summary', 'features', 'best_for_text', 'not_for_text', 'faq', 'seo_title', 'meta_description', 'generation_status', 'generation_noindex', 'generation_review_required']);
        });
    }
};

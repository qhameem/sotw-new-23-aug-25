<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('submittable')->default(true);
            $table->boolean('in_submission_plan')->default(false)->index();
            $table->string('merge_target_slug')->nullable();
            $table->string('submit_label')->nullable();
            $table->string('canonical_slug')->nullable();
            $table->string('legacy_page_type')->nullable();
        });

        Schema::create('category_merge_type_backups', function (Blueprint $table) {
            $table->foreignId('category_id')->primary()->constrained('categories')->cascadeOnDelete();
            $table->string('slug');
            $table->json('original_types');
            $table->json('original_pivots');
            $table->unsignedBigInteger('created_type_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_merge_type_backups');
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['in_submission_plan']);
            $table->dropColumn(['submittable', 'in_submission_plan', 'merge_target_slug', 'submit_label', 'canonical_slug', 'legacy_page_type']);
        });
    }
};

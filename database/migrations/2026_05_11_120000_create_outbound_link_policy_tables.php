<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('outbound_link_rules')) {
            Schema::create('outbound_link_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('match_type', 32);
                $table->string('pattern');
                $table->string('source_scope', 64)->default('all');
                $table->boolean('rel_nofollow')->default(true);
                $table->boolean('rel_ugc')->default(false);
                $table->boolean('rel_sponsored')->default(false);
                $table->boolean('rel_noopener')->default(true);
                $table->boolean('rel_noreferrer')->default(true);
                $table->integer('priority')->default(100);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'source_scope', 'priority']);
                $table->index(['match_type', 'pattern']);
            });
        }

        if (! Schema::hasTable('outbound_link_occurrences')) {
            Schema::create('outbound_link_occurrences', function (Blueprint $table) {
                $table->id();
                $table->string('occurrence_key')->unique();
                $table->string('normalized_url');
                $table->string('original_url')->nullable();
                $table->string('domain')->nullable();
                $table->string('path')->nullable();
                $table->string('source_type', 64);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_title')->nullable();
                $table->string('source_admin_url')->nullable();
                $table->string('anchor_text')->nullable();
                $table->string('detected_rel')->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->unsignedInteger('occurrence_count')->default(1);
                $table->timestamps();

                $table->index(['domain', 'source_type']);
                $table->index(['source_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_link_occurrences');
        Schema::dropIfExists('outbound_link_rules');
    }
};

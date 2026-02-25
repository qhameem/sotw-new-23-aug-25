<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('submission_type')->default('free')->after('is_promoted'); // 'free' or 'badge'
            $table->boolean('badge_verified')->default(false)->after('submission_type');
            $table->timestamp('badge_verified_at')->nullable()->after('badge_verified');
            $table->unsignedTinyInteger('badge_consecutive_failures')->default(0)->after('badge_verified_at');
            $table->string('badge_placement_url')->nullable()->after('badge_consecutive_failures');
            $table->timestamp('badge_warning_sent_at')->nullable()->after('badge_placement_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'submission_type',
                'badge_verified',
                'badge_verified_at',
                'badge_consecutive_failures',
                'badge_placement_url',
                'badge_warning_sent_at',
            ]);
        });
    }
};

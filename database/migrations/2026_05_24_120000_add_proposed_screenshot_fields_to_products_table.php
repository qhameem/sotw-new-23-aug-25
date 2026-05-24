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
        Schema::table('products', function (Blueprint $table) {
            $table->string('proposed_screenshot_path')->nullable()->after('proposed_logo_path');
            $table->string('proposed_screenshot_thumb_path')->nullable()->after('proposed_screenshot_path');
            $table->string('proposed_screenshot_medium_path')->nullable()->after('proposed_screenshot_thumb_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'proposed_screenshot_path',
                'proposed_screenshot_thumb_path',
                'proposed_screenshot_medium_path',
            ]);
        });
    }
};

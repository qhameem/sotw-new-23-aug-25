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
        Schema::table('ad_zones', function (Blueprint $table) {
            $table->unsignedInteger('display_after_nth_product')->nullable()->after('slug')->comment('Display ad after Nth product in a list; only relevant for specific zone types.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_zones', function (Blueprint $table) {
            $table->dropColumn('display_after_nth_product');
        });
    }
};
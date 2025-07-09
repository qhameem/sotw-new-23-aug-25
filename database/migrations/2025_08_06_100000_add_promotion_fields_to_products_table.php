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
            $table->boolean('is_promoted')->default(false)->after('approved')->index();
            $table->unsignedInteger('promoted_position')->nullable()->unique()->after('is_promoted')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // When dropping a unique column, it's good practice to drop the unique index first if named explicitly.
            // However, Laravel's dropColumn should handle it. If issues, use: $table->dropUnique('products_promoted_position_unique');
            $table->dropColumn('promoted_position');
            $table->dropColumn('is_promoted');
        });
    }
};
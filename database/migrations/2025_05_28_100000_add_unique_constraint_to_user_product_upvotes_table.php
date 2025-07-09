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
        Schema::table('user_product_upvotes', function (Blueprint $table) {
            // Define the index name
            $indexName = 'user_product_upvotes_user_id_product_id_unique';

            // Check if the index does not already exist
            if (!Schema::hasIndex('user_product_upvotes', $indexName)) {
                // Add unique constraint for user_id and product_id
                $table->unique(['user_id', 'product_id'], $indexName);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_product_upvotes', function (Blueprint $table) {
            // Define the index name
            $indexName = 'user_product_upvotes_user_id_product_id_unique';

            // Check if the index exists before attempting to drop it
            if (Schema::hasIndex('user_product_upvotes', $indexName)) {
                $table->dropUnique($indexName);
            }
        });
    }
};
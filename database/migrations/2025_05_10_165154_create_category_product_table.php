<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->unique(['product_id', 'category_id']);
        });

        // Migrate existing data from products.category_id
        if (Schema::hasColumn('products', 'category_id')) {
            $products = DB::table('products')->select('id', 'category_id')->whereNotNull('category_id')->get();
            foreach ($products as $product) {
                DB::table('category_product')->insert([
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};

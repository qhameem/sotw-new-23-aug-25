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
            $table->string('product_page_tagline')->nullable();
        });

        \Illuminate\Support\Facades\DB::table('products')->update([
            'product_page_tagline' => \Illuminate\Support\Facades\DB::raw('tagline')
        ]);

        Schema::table('products', function (Blueprint $table) {
            $table->string('product_page_tagline')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('product_page_tagline');
        });
    }
};

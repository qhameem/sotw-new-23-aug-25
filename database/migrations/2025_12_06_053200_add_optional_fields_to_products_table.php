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
            $table->json('maker_links')->nullable()->after('link');
            $table->boolean('sell_product')->default(false)->after('maker_links');
            $table->decimal('asking_price', 10, 2)->nullable()->after('sell_product');
            $table->string('x_account')->nullable()->after('asking_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'maker_links',
                'sell_product',
                'asking_price',
                'x_account'
            ]);
        });
    }
};
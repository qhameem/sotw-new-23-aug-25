<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('proposed_name')->nullable()->after('name');
            $table->string('proposed_link')->nullable()->after('link');
            $table->string('proposed_video_url')->nullable()->after('video_url');
            $table->string('proposed_x_account')->nullable()->after('x_account');
            $table->boolean('proposed_sell_product')->nullable()->after('sell_product');
            $table->decimal('proposed_asking_price', 10, 2)->nullable()->after('asking_price');
            $table->json('proposed_maker_links')->nullable()->after('maker_links');
            $table->string('proposed_product_page_tagline')->nullable()->after('product_page_tagline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'proposed_name',
                'proposed_link',
                'proposed_video_url',
                'proposed_x_account',
                'proposed_sell_product',
                'proposed_asking_price',
                'proposed_maker_links',
                'proposed_product_page_tagline',
            ]);
        });
    }
};

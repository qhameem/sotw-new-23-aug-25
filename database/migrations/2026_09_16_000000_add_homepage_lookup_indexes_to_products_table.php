<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['approved', 'is_published', 'published_at'], 'products_live_published_at_index');
            $table->index(['approved', 'is_published', 'created_at'], 'products_live_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_live_published_at_index');
            $table->dropIndex('products_live_created_at_index');
        });
    }
};

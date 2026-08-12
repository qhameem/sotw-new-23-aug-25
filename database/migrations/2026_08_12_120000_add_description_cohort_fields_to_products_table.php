<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('description_format', 20)->default('full')->after('description');
            $table->json('product_facts')->nullable()->after('description_format');
            $table->string('content_test_group', 20)->nullable()->after('product_facts');
            $table->timestamp('content_test_started_at')->nullable()->after('content_test_group');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description_format', 'product_facts', 'content_test_group', 'content_test_started_at']);
        });
    }
};

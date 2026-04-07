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
        Schema::table('code_snippets', function (Blueprint $table) {
            $table->json('excluded_ips')->nullable()->after('code');
            $table->json('excluded_countries')->nullable()->after('excluded_ips');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('code_snippets', function (Blueprint $table) {
            $table->dropColumn(['excluded_ips', 'excluded_countries']);
        });
    }
};

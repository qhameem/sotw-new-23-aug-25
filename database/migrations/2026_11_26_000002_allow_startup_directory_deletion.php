<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startup_directories', function (Blueprint $table) {
            $table->string('original_website_url', 2048)->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('startup_directories', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('original_website_url');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_submission_drafts', function (Blueprint $table) {
            $table->json('regeneration_counts')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('product_submission_drafts', function (Blueprint $table) {
            $table->dropColumn('regeneration_counts');
        });
    }
};

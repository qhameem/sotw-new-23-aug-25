<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the enum values to include 'sidebar'
        DB::statement("ALTER TABLE code_snippets MODIFY COLUMN location ENUM('head', 'body', 'sidebar')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'sidebar' from the enum values
        DB::statement("ALTER TABLE code_snippets MODIFY COLUMN location ENUM('head', 'body')");
    }
};

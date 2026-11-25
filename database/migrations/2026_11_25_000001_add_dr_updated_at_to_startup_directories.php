<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startup_directories', fn (Blueprint $table) => $table->timestamp('dr_updated_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('startup_directories', fn (Blueprint $table) => $table->dropColumn('dr_updated_at'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tool_users', function (Blueprint $table) {
            $table->string('appearance_preference', 16)->default('dark')->after('google_avatar');
        });
    }

    public function down(): void
    {
        Schema::table('tool_users', function (Blueprint $table) {
            $table->dropColumn('appearance_preference');
        });
    }
};

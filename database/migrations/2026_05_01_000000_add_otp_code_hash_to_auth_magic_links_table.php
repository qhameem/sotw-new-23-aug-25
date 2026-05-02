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
        Schema::table('auth_magic_links', function (Blueprint $table) {
            $table->string('otp_code_hash', 64)->nullable()->after('token_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_magic_links', function (Blueprint $table) {
            $table->dropColumn('otp_code_hash');
        });
    }
};

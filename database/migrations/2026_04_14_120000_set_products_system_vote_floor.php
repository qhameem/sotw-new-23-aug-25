<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')
            ->where('votes_count', 0)
            ->update(['votes_count' => 1]);

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('votes_count')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('votes_count')->default(0)->change();
        });
    }
};

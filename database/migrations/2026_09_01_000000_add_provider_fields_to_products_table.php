<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('hosting_provider')->nullable();
            $table->string('domain_registrar')->nullable();
            $table->string('proposed_hosting_provider')->nullable();
            $table->string('proposed_domain_registrar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hosting_provider', 'domain_registrar', 'proposed_hosting_provider', 'proposed_domain_registrar']);
        });
    }
};

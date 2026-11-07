<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('custom_pages', fn (Blueprint $table) => $table->json('text_styles')->nullable()); }
    public function down(): void { Schema::table('custom_pages', fn (Blueprint $table) => $table->dropColumn('text_styles')); }
};

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::create('auth_magic_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->string('redirect_to')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->string('requested_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_magic_links');

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });
    }
};

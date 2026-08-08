<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_verification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('trigger', 40);
            $table->string('url', 2048);
            $table->boolean('verified');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('response_hash', 64)->nullable();
            $table->text('matched_element')->nullable();
            $table->text('message')->nullable();
            $table->ipAddress('request_ip')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['product_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_verification_attempts');
    }
};

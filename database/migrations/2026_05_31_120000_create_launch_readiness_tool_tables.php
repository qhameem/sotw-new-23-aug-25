<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('google_avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tool_auth_magic_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_user_id')->nullable()->constrained('tool_users')->nullOnDelete();
            $table->string('email')->index();
            $table->string('token_hash', 64)->unique();
            $table->string('otp_code_hash', 64)->nullable();
            $table->string('redirect_to')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->string('requested_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('tool_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_user_id')->nullable()->constrained('tool_users')->nullOnDelete();
            $table->string('tool_key')->default('launch_readiness');
            $table->string('result_token')->unique();
            $table->string('submitted_url', 2048);
            $table->string('normalized_url', 2048);
            $table->string('final_url', 2048)->nullable();
            $table->string('final_host')->nullable()->index();
            $table->string('guest_hash', 64)->nullable()->index();
            $table->unsignedTinyInteger('launch_score')->default(0);
            $table->unsignedTinyInteger('seo_score')->default(0);
            $table->unsignedTinyInteger('ai_score')->default(0);
            $table->unsignedTinyInteger('trust_score')->default(0);
            $table->unsignedInteger('passed_checks')->default(0);
            $table->unsignedInteger('warning_checks')->default(0);
            $table->unsignedInteger('failed_checks')->default(0);
            $table->string('status_label')->default('Needs improvement');
            $table->boolean('save_to_history')->default(true)->index();
            $table->json('audit_payload');
            $table->timestamp('scanned_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_scans');
        Schema::dropIfExists('tool_auth_magic_links');
        Schema::dropIfExists('tool_users');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_error_reports', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('severity', 20)->default('error')->index();
            $table->string('source', 120)->index();
            $table->string('summary');
            $table->text('details')->nullable();
            $table->json('context')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->timestamp('first_occurred_at');
            $table->timestamp('last_occurred_at')->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_reports');
    }
};

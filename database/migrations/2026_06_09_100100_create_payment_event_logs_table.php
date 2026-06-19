<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_event_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('provider_event_id')->unique();
            $table->string('event_type');
            $table->foreignId('paid_submission_checkout_id')->nullable()->constrained('paid_submission_checkouts')->nullOnDelete();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_event_logs');
    }
};

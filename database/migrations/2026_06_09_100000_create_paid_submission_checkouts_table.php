<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paid_submission_checkouts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('product_name');
            $table->string('product_link', 2048);
            $table->date('schedule_date');
            $table->unsignedInteger('amount_cents')->default(999);
            $table->string('currency', 3)->default('usd');
            $table->json('submission_payload');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_event_id')->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->text('failure_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paid_submission_checkouts');
    }
};

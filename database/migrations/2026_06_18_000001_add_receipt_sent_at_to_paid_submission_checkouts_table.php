<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paid_submission_checkouts', function (Blueprint $table) {
            $table->timestamp('receipt_sent_at')->nullable()->after('schedule_date_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('paid_submission_checkouts', function (Blueprint $table) {
            $table->dropColumn('receipt_sent_at');
        });
    }
};

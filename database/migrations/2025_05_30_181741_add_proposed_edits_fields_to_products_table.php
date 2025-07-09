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
            Schema::table('products', function (Blueprint $table) {
                $table->string('proposed_logo_path')->nullable()->after('logo');
                $table->string('proposed_tagline', 150)->nullable()->after('tagline');
                $table->text('proposed_description')->nullable()->after('description');
                $table->boolean('has_pending_edits')->default(false)->index()->after('approved');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn([
                    'proposed_logo_path',
                    'proposed_tagline',
                    'proposed_description',
                    'has_pending_edits',
                ]);
            });
        }
    };

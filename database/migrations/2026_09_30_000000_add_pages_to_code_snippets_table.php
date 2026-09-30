<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('code_snippets', function (Blueprint $table) {
            $table->json('pages')->nullable()->after('page');
        });

        DB::table('code_snippets')->orderBy('id')->each(function (object $snippet): void {
            DB::table('code_snippets')->where('id', $snippet->id)->update([
                'pages' => json_encode([$snippet->page], JSON_THROW_ON_ERROR),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('code_snippets', function (Blueprint $table) {
            $table->dropColumn('pages');
        });
    }
};

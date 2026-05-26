<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_handle')->nullable()->unique()->after('name');
        });

        $usedHandles = DB::table('users')
            ->whereNotNull('public_handle')
            ->pluck('public_handle')
            ->filter()
            ->values()
            ->all();

        $usersWithCollections = DB::table('users')
            ->join('product_collections', 'product_collections.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.email', 'users.public_handle')
            ->distinct()
            ->orderBy('users.id')
            ->get();

        foreach ($usersWithCollections as $user) {
            if (filled($user->public_handle)) {
                continue;
            }

            $baseHandle = Str::slug($user->name ?: Str::before((string) $user->email, '@'));
            $baseHandle = $baseHandle !== '' ? $baseHandle : 'member';
            $handle = $baseHandle;
            $counter = 2;

            while (in_array($handle, $usedHandles, true)) {
                $handle = $baseHandle . '-' . $counter++;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['public_handle' => $handle]);

            $usedHandles[] = $handle;
        }

        Schema::table('product_collections', function (Blueprint $table) {
            $table->dropUnique('product_collections_slug_unique');
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('product_collections', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->unique('slug');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_public_handle_unique');
            $table->dropColumn('public_handle');
        });
    }
};

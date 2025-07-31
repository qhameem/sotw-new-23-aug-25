<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version')->nullable(); // e.g., v1.2.0 or leave blank
            $table->date('released_at'); // release date
            $table->enum('type', ['added', 'changed', 'fixed', 'removed'])->index();
            $table->string('title'); // short description
            $table->text('description')->nullable(); // optional detailed description
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
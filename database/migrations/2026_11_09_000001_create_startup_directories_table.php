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
        $rows = $this->readArchive();

        Schema::create('startup_directories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('website_url', 2048);
            $table->string('submission_url', 2048)->nullable();
            $table->string('category', 32)->nullable()->index();
            $table->string('best_for', 255)->nullable();
            $table->string('pricing', 16)->nullable()->index();
            $table->string('price_details', 255)->nullable();
            $table->string('link_type', 16)->nullable()->index();
            $table->unsignedTinyInteger('dr')->nullable()->index();
            $table->unsignedInteger('priority')->default(0)->index();
            $table->string('source_url', 2048)->nullable();
            $table->text('requirements')->nullable();
            $table->date('verified_on')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('custom_pages', function (Blueprint $table) {
            $table->string('directory_heading')->nullable();
            $table->text('directory_intro')->nullable();
        });

        foreach (array_chunk($rows, 50) as $batch) {
            DB::table('startup_directories')->insert($batch);
        }
    }

    public function down(): void
    {
        Schema::table('custom_pages', fn (Blueprint $table) => $table->dropColumn(['directory_heading', 'directory_intro']));
        Schema::dropIfExists('startup_directories');
    }

    private function readArchive(): array
    {
        $file = fopen(base_path('launchpanda-best-startup-directories.csv'), 'r');
        $rows = [];
        $timestamp = now();
        $editorial = [
            'producthunt.com' => ['launch', 'Launch-day product discovery', 100],
            'saashub.com' => ['discovery', 'Software alternatives and comparisons', 90],
            'g2.com' => ['reviews', 'B2B software buyers', 80],
            'capterra.com' => ['reviews', 'Business software reviews and comparisons', 70],
            'betalist.com' => ['launch', 'Early-stage startups and beta launches', 60],
            'alternativeto.net' => ['discovery', 'Software alternatives', 50],
            'sourceforge.net' => ['discovery', 'Open source projects and software discovery', 40],
        ];

        try {
            if (fgetcsv($file, escape: '') !== ['Directory name', 'URL', 'DR', 'Link type', 'Price']) {
                throw new RuntimeException('The startup directories CSV has an unexpected header.');
            }

            while (($row = fgetcsv($file, escape: '')) !== false) {
                [$name, $url, $dr, $link, $price] = $row;
                if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    throw new RuntimeException("Invalid URL in directory archive: {$name}");
                }

                $domain = Str::replaceStart('www.', '', strtolower(parse_url($url, PHP_URL_HOST)));
                [$category, $bestFor, $priority] = $editorial[$domain] ?? [null, null, 0];

                $rows[] = [
                    'name' => $name,
                    'website_url' => $url,
                    'category' => $category,
                    'best_for' => $bestFor,
                    'priority' => $priority,
                    'dr' => $dr !== '' ? (int) $dr : null,
                    'link_type' => match (strtolower($link)) {
                        'dofollow' => 'dofollow',
                        'nofollow' => 'nofollow',
                        'conditional', 'dofollow with badge' => 'conditional',
                        default => null,
                    },
                    'pricing' => match (true) {
                        in_array(strtolower($price), ['free', 'free*'], true) => 'free',
                        Str::startsWith(strtolower($price), ['free*;', 'freemium']) => 'freemium',
                        Str::startsWith(strtolower($price), 'paid') => 'paid',
                        default => null,
                    },
                    'price_details' => $price !== '' ? $price : null,
                    'requirements' => strtolower($link) === 'dofollow with badge' ? 'Dofollow requires a badge.' : null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        } finally {
            fclose($file);
        }

        return $rows;
    }
};

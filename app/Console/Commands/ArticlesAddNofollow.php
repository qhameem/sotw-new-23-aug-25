<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ArticlesAddNofollow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:add-nofollow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds rel="nofollow" to all links in article content.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to process articles...');

        \App\Models\Article::chunk(100, function ($articles) {
            foreach ($articles as $article) {
                $this->info("Processing article ID: {$article->id}");
                $article->save();
            }
        });

        $this->info('All articles processed successfully.');
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Markdown\CacheVersion;
use App\Services\Markdown\Discovery;
use Illuminate\Console\Command;

class RefreshAgentDocuments extends Command
{
    protected $signature = 'agent:refresh';

    protected $description = 'Invalidate and warm anonymous agent discovery documents';

    public function handle(Discovery $discovery, CacheVersion $version): int
    {
        $version->invalidate();
        $documents = $discovery->documents();
        foreach ($documents as $name => $body) {
            $this->line($name.': '.strlen($body).' bytes');
        }

        return self::SUCCESS;
    }
}

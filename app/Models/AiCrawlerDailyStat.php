<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCrawlerDailyStat extends Model
{
    protected $fillable = [
        'visited_on',
        'bot',
        'path_hash',
        'path',
        'status_code',
        'requests',
        'last_seen_at',
    ];

    protected $casts = [
        'visited_on' => 'date',
        'requests' => 'integer',
        'last_seen_at' => 'datetime',
    ];
}

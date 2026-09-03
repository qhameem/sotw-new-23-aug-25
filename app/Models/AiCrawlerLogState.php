<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCrawlerLogState extends Model
{
    protected $fillable = [
        'path_hash',
        'path',
        'inode',
        'byte_offset',
        'last_imported_at',
    ];

    protected $casts = [
        'byte_offset' => 'integer',
        'last_imported_at' => 'datetime',
    ];
}

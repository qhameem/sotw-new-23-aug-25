<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMetaTag extends Model
{
    protected $table = 'page_meta_tags';

    protected $fillable = [
        'page_id',
        'meta_title',
        'meta_description',
    ];
}

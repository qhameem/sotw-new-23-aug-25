<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject',
        'body',
        'is_html',
        'from_name',
        'from_email',
        'reply_to_email',
        'allowed_variables',
    ];

    protected $casts = [
        'allowed_variables' => 'array',
        'is_html' => 'boolean',
    ];
}

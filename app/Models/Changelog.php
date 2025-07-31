<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'released_at',
        'type',
        'title',
        'description',
    ];

    protected $casts = [
        'released_at' => 'date',
    ];
}
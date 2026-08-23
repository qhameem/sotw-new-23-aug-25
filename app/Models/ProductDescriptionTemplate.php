<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDescriptionTemplate extends Model
{
    protected $fillable = ['name', 'instruction', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}

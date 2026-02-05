<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'path',
        'path_thumb',
        'path_medium',
        'alt_text',
        'type',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

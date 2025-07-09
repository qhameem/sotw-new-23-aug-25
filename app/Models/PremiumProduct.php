<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumProduct extends Model
{
    protected $fillable = [
        'product_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

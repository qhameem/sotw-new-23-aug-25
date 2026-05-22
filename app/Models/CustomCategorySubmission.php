<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomCategorySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type', // 'category', 'use_case', 'best_for', 'platform', 'tech_stack'
        'name',
        'status', // 'pending', 'approved', 'rejected'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'product_url',
        'product_creator',
        'email',
        'access_instructions',
        'other_instructions',
        'is_done',
        'review_url',
    ];
}

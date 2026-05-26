<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCollectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_collection_id',
        'product_id',
        'comment',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'product_collection_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

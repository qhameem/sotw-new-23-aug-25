<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductWeekStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'week_start',
        'manual_upvotes',
        'list_impressions',
        'detail_views',
        'outbound_clicks',
        'ranking_score',
        'final_rank',
        'is_finalized',
    ];

    protected $casts = [
        'week_start' => 'date',
        'ranking_score' => 'decimal:4',
        'is_finalized' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

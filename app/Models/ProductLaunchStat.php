<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLaunchStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'launch_window_start',
        'launch_window_end',
        'manual_upvotes',
        'list_impressions',
        'detail_views',
        'outbound_clicks',
        'exploration_score',
        'last_served_at',
    ];

    protected $casts = [
        'launch_window_start' => 'datetime',
        'launch_window_end' => 'datetime',
        'exploration_score' => 'decimal:4',
        'last_served_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

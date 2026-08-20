<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'first_name',
        'status',
        'source',
        'consented_at',
        'synced_at',
        'provider_contact_id',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}

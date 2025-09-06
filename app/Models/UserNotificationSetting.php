<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_addresses',
        'timezone',
    ];

    protected $casts = [
        'email_addresses' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

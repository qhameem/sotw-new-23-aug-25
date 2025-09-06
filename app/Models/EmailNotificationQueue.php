<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailNotificationQueue extends Model
{
    use HasFactory;

    protected $table = 'email_notification_queue';

    protected $fillable = [
        'user_id',
        'todo_list_item_id',
        'email_address',
        'send_at',
        'sent_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function todoListItem()
    {
        return $this->belongsTo(TodoListItem::class);
    }
}

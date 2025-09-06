<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'title',
        'description',
        'deadline',
        'is_completed',
        'color',
    ];

    public function todoList()
    {
        return $this->belongsTo(TodoList::class);
    }
}

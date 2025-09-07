<?php

namespace App\Http\View\Composers;

use App\Models\TodoList;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TodoListComposer
{
    public function compose(View $view)
    {
        $user = Auth::user();
        $lists = $user ? TodoList::where('user_id', $user->id)->with('items')->get() : collect();
        $view->with('lists', $lists);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\TodoListItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TodoListController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        if (request()->wantsJson()) {
            $user = Auth::user();
            $lists = $user ? TodoList::where('user_id', $user->id)->with('items')->get() : [];
            return response()->json($lists);
        }

        return view('todolists.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $list = Auth::user()->todoLists()->create([
            'title' => $request->title,
        ]);

        return response()->json($list->load('items'));
    }

    public function update(Request $request, TodoList $todoList)
    {
        $this->authorize('update', $todoList);

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todoList->update($request->only('title'));

        return response()->json($todoList->load('items'));
    }

    public function destroy(TodoList $todoList)
    {
        $this->authorize('delete', $todoList);

        $todoList->delete();

        return response()->json(['success' => true]);
    }

    public function storeItem(Request $request, TodoList $todoList)
    {
        $this->authorize('update', $todoList);

        $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
        ]);

        $item = $todoList->items()->create([
            'title' => $request->title,
            'color' => $request->color ?? 'gray',
            'deadline' => $request->deadline,
        ]);

        return response()->json($item);
    }

    public function updateItem(Request $request, TodoListItem $todoListItem)
    {
        $this->authorize('update', $todoListItem->todoList);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'completed' => 'sometimes|boolean',
            'color' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
        ]);

        $todoListItem->update($request->only('title', 'completed', 'color', 'deadline'));

        return response()->json($todoListItem);
    }

    public function destroyItem(TodoListItem $todoListItem)
    {
        $this->authorize('update', $todoListItem->todoList);

        $todoListItem->delete();

        return response()->json(['success' => true]);
    }
}

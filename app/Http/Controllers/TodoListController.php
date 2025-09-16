<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\TodoListItem;
use App\Models\PageMetaTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Exports\TodoListExport;
use Maatwebsite\Excel\Facades\Excel;

class TodoListController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        if (request()->wantsJson()) {
            $lists = TodoList::with('items')->get();
            return response()->json($lists);
        }

        $seoSettings = PageMetaTag::where('path', '/free-todo-list-tool')->first();
        $meta_title = $seoSettings->meta_title ?? 'Free To Do List Tool - Software on the Web';
        $meta_description = $seoSettings->meta_description ?? '';
        $lists = TodoList::with('items')->get();

        return view('todolists.index', compact('meta_title', 'meta_description', 'lists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $list = new TodoList(['title' => $request->title]);

        if (Auth::check()) {
            $list->user_id = Auth::id();
        }

        $list->save();

        return response()->json($list->load('items'));
    }

    public function update(Request $request, TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('update', $todoList);
        }

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $todoList->update($request->only('title'));

        return response()->json($todoList->load('items'));
    }

    public function destroy(TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('delete', $todoList);
        }

        $todoList->delete();

        return response()->json(['success' => true]);
    }

    public function export(TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('view', $todoList);
        }

        return Excel::download(new TodoListExport($todoList), $todoList->title . '.xlsx');
    }

    public function storeItem(Request $request, TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('update', $todoList);
        }

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
        if (Auth::check()) {
            $this->authorize('update', $todoListItem->todoList);
        }

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
        if (Auth::check()) {
            $this->authorize('update',
            $todoListItem->todoList);
        }

        $todoListItem->delete();

        return response()->json(['success' => true]);
    }
}

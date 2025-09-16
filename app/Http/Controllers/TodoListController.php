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
        $lists = [];
        if (Auth::check()) {
            $lists = TodoList::where('user_id', Auth::id())->with('items')->get();
        } else {
            $lists = session('todo_lists', []);
        }

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
            $list->save();
            return response()->json($list->load('items'));
        } else {
            // For guest users, add to session
            $lists = session('todo_lists', []);
            $list->id = time(); // Assign a temporary unique ID
            $list->items = [];
            $lists[] = $list;
            session(['todo_lists' => $lists]);
            return response()->json($list);
        }
    }

    public function update(Request $request, TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('update', $todoList);
            $todoList->update($request->only('title'));
            return response()->json($todoList->load('items'));
        } else {
            $lists = session('todo_lists', []);
            foreach ($lists as $key => $list) {
                if ($list->id == $request->route('todoList')) {
                    $lists[$key]->title = $request->title;
                    break;
                }
            }
            session(['todo_lists' => $lists]);
            return response()->json($lists);
        }
    }

    public function updateName(Request $request, TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('update', $todoList);
            $request->validate(['title' => 'required|string|max:255']);
            $todoList->update(['title' => $request->title]);
            return response()->json($todoList);
        } else {
            $lists = session('todo_lists', []);
            foreach ($lists as $key => $list) {
                if ($list->id == $request->route('todoList')) {
                    $lists[$key]->title = $request->title;
                    break;
                }
            }
            session(['todo_lists' => $lists]);
            return response()->json($lists);
        }
    }

    public function destroy(TodoList $todoList)
    {
        if (Auth::check()) {
            $this->authorize('delete', $todoList);
            $todoList->delete();

            $remainingLists = TodoList::where('user_id', Auth::id())->with('items')->get();
            return response()->json(['success' => true, 'lists' => $remainingLists]);
        } else {
            $lists = session('todo_lists', []);
            $lists = array_values(array_filter($lists, function ($list) use ($todoList) {
                return $list->id != $todoList->id;
            }));
            session(['todo_lists' => $lists]);
            return response()->json(['success' => true, 'lists' => $lists]);
        }
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
            $item = $todoList->items()->create($request->all());
            return response()->json($item);
        } else {
            $lists = session('todo_lists', []);
            foreach ($lists as $key => $list) {
                if ($list->id == $todoList->id) {
                    $item = new \stdClass();
                    $item->id = time();
                    $item->title = $request->title;
                    $item->completed = false;
                    $item->color = $request->color ?? 'gray';
                    $item->deadline = $request->deadline;
                    $lists[$key]->items[] = $item;
                    break;
                }
            }
            session(['todo_lists' => $lists]);
            return response()->json($item);
        }
    }

    public function updateItem(Request $request, TodoListItem $todoListItem)
    {
        if (Auth::check()) {
            $this->authorize('update', $todoListItem->todoList);
            $todoListItem->update($request->all());
            return response()->json($todoListItem);
        } else {
            $lists = session('todo_lists', []);
            foreach ($lists as $listKey => $list) {
                foreach ($list->items as $itemKey => $item) {
                    if ($item->id == $todoListItem->id) {
                        $lists[$listKey]->items[$itemKey]->title = $request->input('title', $item->title);
                        $lists[$listKey]->items[$itemKey]->completed = $request->input('completed', $item->completed);
                        $lists[$listKey]->items[$itemKey]->color = $request->input('color', $item->color);
                        $lists[$listKey]->items[$itemKey]->deadline = $request->input('deadline', $item->deadline);
                        break 2;
                    }
                }
            }
            session(['todo_lists' => $lists]);
            return response()->json($lists);
        }
    }

    public function destroyItem(TodoListItem $todoListItem)
    {
        if (Auth::check()) {
            $this->authorize('update', $todoListItem->todoList);
            $todoListItem->delete();
        } else {
            $lists = session('todo_lists', []);
            foreach ($lists as $listKey => $list) {
                foreach ($list->items as $itemKey => $item) {
                    if ($item->id == $todoListItem->id) {
                        unset($lists[$listKey]->items[$itemKey]);
                        break 2;
                    }
                }
            }
            session(['todo_lists' => $lists]);
        }

        return response()->json(['success' => true]);
    }
}

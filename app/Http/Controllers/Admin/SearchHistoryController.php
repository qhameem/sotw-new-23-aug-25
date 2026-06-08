<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchLog;
use Illuminate\View\View;

class SearchHistoryController extends Controller
{
    public function index(): View
    {
        $searchLogs = SearchLog::query()
            ->with('user')
            ->latest()
            ->paginate(25);

        return view('admin.search-history.index', [
            'searchLogs' => $searchLogs,
            'searchLogCount' => SearchLog::count(),
        ]);
    }
}

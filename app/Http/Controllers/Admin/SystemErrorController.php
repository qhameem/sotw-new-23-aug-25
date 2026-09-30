<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemErrorReport;
use Illuminate\Http\Request;

class SystemErrorController extends Controller
{
    public function index(Request $request)
    {
        $reports = SystemErrorReport::query()
            ->with('user:id,name,email')
            ->when($request->filled('severity'), fn ($query) => $query->where('severity', $request->string('severity')))
            ->when($request->boolean('unresolved'), fn ($query) => $query->whereNull('resolved_at'))
            ->latest('last_occurred_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.errors.index', compact('reports'));
    }

    public function resolve(SystemErrorReport $systemErrorReport)
    {
        $systemErrorReport->update(['resolved_at' => now()]);

        return back()->with('success', 'Error marked as resolved.');
    }
}

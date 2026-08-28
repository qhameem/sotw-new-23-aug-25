<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductSubmissionDraft;
use Illuminate\View\View;

class ProductSubmissionDraftController extends Controller
{
    public function index(): View
    {
        $drafts = ProductSubmissionDraft::query()
            ->with('user:id,name,email')
            ->latest('last_autosaved_at')
            ->latest('updated_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.product_submission_drafts.index', compact('drafts'));
    }
}

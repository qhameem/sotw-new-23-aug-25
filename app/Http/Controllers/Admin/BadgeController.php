<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $badges = Badge::all();
        return view('admin.badges.index', compact('badges'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.badges.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'alt_text' => 'required|string|max:255',
            'badge' => 'required|image',
        ]);

        $file = $request->file('badge');
        $originalExtension = $file->getClientOriginalExtension();
        $fileName = Str::random(40);

        if ($originalExtension !== 'svg') {
            $fileName .= '.svg';
            $directoryPath = storage_path('app/public/badges');
            if (!File::isDirectory($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true, true);
            }
            $path = 'public/badges/' . $fileName;
            Image::load($file->getRealPath())
                ->save(storage_path('app/' . $path));
        } else {
            $fileName .= '.svg';
            $path = $file->storeAs('public/badges', $fileName);
        }

        Badge::create([
            'title' => $request->title,
            'alt_text' => $request->alt_text,
            'path' => Storage::url($path),
        ]);

        return redirect()->route('admin.badges.index')->with('success', 'Badge uploaded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Badge $badge)
    {
        // Delete the badge file from storage
        $path = str_replace('/storage', 'public', $badge->path);
        Storage::delete($path);

        $badge->delete();

        return redirect()->route('admin.badges.index')->with('success', 'Badge deleted successfully.');
    }
}

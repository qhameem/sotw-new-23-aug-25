<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // Added Log facade
use Illuminate\Support\Facades\Storage; // Added Storage facade

class ArticlePostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // TODO: Implement search and sorting
        $paginatedPosts = Article::with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(15);

        $itemsToView = [];
        $slugsFixed = false;

        foreach ($paginatedPosts->items() as $post) {
            $currentPost = $post; // Work with a copy in case fresh() returns null
            if (empty($currentPost->slug) && $currentPost->exists) {
                Log::warning("ArticlePostController@index: Post ID {$currentPost->id} (Title: '{$currentPost->title}') has an empty slug. Attempting to fix.");
                try {
                    $currentPost->save(); // Trigger model's saving event to generate slug
                    $freshPost = $currentPost->fresh(); // Get the absolute latest from DB
                    if ($freshPost) {
                        $itemsToView[] = $freshPost;
                        Log::info("ArticlePostController@index: Slug for Post ID {$freshPost->id} (Title: '{$freshPost->title}') fixed to '{$freshPost->slug}'.");
                        $slugsFixed = true;
                    } else {
                        Log::error("ArticlePostController@index: Failed to refresh Post ID {$currentPost->id} after attempting to fix slug. Using original potentially problematic post data for view.");
                        $itemsToView[] = $currentPost; // Add original if fresh fails
                    }
                } catch (\Exception $e) {
                    Log::error("ArticlePostController@index: Failed to fix slug for Post ID {$currentPost->id}: " . $e->getMessage() . ". Using original post data for view.");
                    $itemsToView[] = $currentPost; // Add original on error
                }
            } else {
                if ($currentPost->exists) {
                     Log::info("ArticlePostController@index: Post ID {$currentPost->id} (Title: '{$currentPost->title}') has slug '{$currentPost->slug}'. No fix needed.");
                }
                $itemsToView[] = $currentPost;
            }
        }
        
        // Reconstruct a paginator instance with the potentially modified items
        // This is a bit manual but ensures the view gets the corrected items.
        // Note: This manual reconstruction is for the items on the *current page only*.
        // If you need to ensure all items across all pages are fixed, a background job or a dedicated script is better.
        $posts = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsToView,
            $paginatedPosts->total(),
            $paginatedPosts->perPage(),
            $paginatedPosts->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.articles.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ArticleCategory::orderBy('name')->get();
        $tags = ArticleTag::orderBy('name')->get();
        $statuses = ['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled']; // Example statuses
        return view('admin.articles.posts.create', compact('categories', 'tags', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'slug'),
            ],
            'content' => 'required|string',
            'status' => 'required|string|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:65535',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:65535',
            'og_image' => 'nullable|string|max:255', // Later, this could be an image upload
            'og_url' => 'nullable|string|max:255|url',
            'twitter_card' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:65535',
            'featured_image_path' => 'nullable|string|max:255', // Later, this could be an image upload
            'categories' => 'nullable|array',
            'categories.*' => 'exists:article_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:article_tags,id',
        ]);

        $post = new Article($validatedData);
        $post->user_id = Auth::id();

        if (empty($validatedData['slug'])) {
            $post->slug = Str::slug($validatedData['title']);
        } else {
            $post->slug = Str::slug($validatedData['slug']); // Ensure slug is sanitized
        }

        // Ensure slug uniqueness again after potential auto-generation or manual input sanitization
        $originalSlug = $post->slug;
        $counter = 1;
        while (Article::where('slug', $post->slug)->exists()) {
            $post->slug = $originalSlug . '-' . $counter++;
        }


        if ($validatedData['status'] === 'published' && empty($validatedData['published_at'])) {
            $post->published_at = now();
        }

        $post->save();

        if (!empty($validatedData['categories'])) {
            $post->categories()->sync($validatedData['categories']);
        }
        if (!empty($validatedData['tags'])) {
            $post->tags()->sync($validatedData['tags']);
        }

        return redirect()->route('admin.articles.posts.index')->with('success', 'Article post created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article) // Route model binding
    {
        // For admin, show might be the same as edit, or a specific admin preview
        return view('admin.articles.posts.show', compact('article'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $postId) // Changed signature to accept $postId
    {
        Log::info("ArticlePostController@edit: Entry point. Attempting to edit post with explicit ID: {$postId}");

        try {
            $article = Article::findOrFail($postId);
            Log::info("ArticlePostController@edit: Successfully fetched Article ID {$article->id} directly. Slug: '{$article->slug}', Title: '{$article->title}'");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("ArticlePostController@edit: Article::findOrFail({$postId}) failed. Post not found. Redirecting.");
            return redirect()->route('admin.articles.posts.index')->with('error', "Post with ID '{$postId}' not found for editing.");
        }

        // At this point, $article exists (it was found by slug).
        // Now, check if its slug property is empty (which would be unusual if found by slug, but could happen with old data).
        if (empty($article->slug)) {
            Log::warning("ArticlePostController@edit: Article ID {$article->id} (Title: '{$article->title}') exists but has an empty slug property. Attempting to fix by saving.");
            try {
                $article->save(); // Trigger the 'saving' event in the model to generate/ensure slug.
                $article->refresh(); // Get the latest state.
                
                if (empty($article->slug)) {
                    Log::critical("ArticlePostController@edit: CRITICAL - Article ID {$article->id} still has an empty slug after save/refresh. Title: '{$article->title}'.");
                    return redirect()->route('admin.articles.posts.index')->with('error', 'Post has a critical slug issue and cannot be edited.');
                }
                Log::info("ArticlePostController@edit: Slug for Article ID {$article->id} was confirmed/fixed to '{$article->slug}'.");
            } catch (\Exception $e) {
                Log::error("ArticlePostController@edit: Error saving Article ID {$article->id} to fix empty slug: " . $e->getMessage());
                return redirect()->route('admin.articles.posts.index')->with('error', 'Error preparing post for editing while trying to fix slug.');
            }
        }

        $categories = ArticleCategory::orderBy('name')->get();
        $tags = ArticleTag::orderBy('name')->get();
        $statuses = ['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled'];
        $article->load('categories', 'tags');
        return view('admin.articles.posts.edit', compact('article', 'categories', 'tags', 'statuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $postId) // Changed signature
    {
        Log::info("ArticlePostController@update: Attempting to update post with explicit ID: {$postId}");
        try {
            $article = Article::findOrFail($postId);
            Log::info("ArticlePostController@update: Successfully fetched Article ID {$article->id} for update.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("ArticlePostController@update: Article::findOrFail({$postId}) failed. Post not found for update. Redirecting.");
            return redirect()->route('admin.articles.posts.index')->with('error', "Post with ID '{$postId}' not found for update.");
        }

        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'slug')->ignore($article->id),
            ],
            'content' => 'required|string',
            'status' => 'required|string|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:65535',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:65535',
            'og_image' => 'nullable|string|max:255',
            'og_url' => 'nullable|string|max:255|url',
            'twitter_card' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:65535',
            'featured_image_path' => 'nullable|string|max:255',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:article_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:article_tags,id',
        ]);

        $article->fill($validatedData);

        if (empty($validatedData['slug'])) {
            $article->slug = Str::slug($validatedData['title']);
        } else {
            $article->slug = Str::slug($validatedData['slug']);
        }
        
        // Ensure slug uniqueness again after potential auto-generation or manual input sanitization
        if ($article->isDirty('slug')) {
            $originalSlug = $article->slug;
            $counter = 1;
            // Check if other posts use this slug
            while (Article::where('slug', $article->slug)->where('id', '!=', $article->id)->exists()) {
                $article->slug = $originalSlug . '-' . $counter++;
            }
        }


        if ($validatedData['status'] === 'published' && empty($validatedData['published_at'])) {
            $article->published_at = now();
        } elseif ($validatedData['status'] !== 'published' && $article->status === 'published') {
            // If changing status from published to something else, nullify published_at if it's not explicitly set
            // Or, admin might want to keep the original published_at date, this logic can be adjusted.
            // For now, let's assume if it's not published, published_at might be cleared or kept as is if scheduled.
            if($validatedData['status'] === 'draft') {
                 $article->published_at = null;
            }
        }


        $article->save();

        $article->categories()->sync($validatedData['categories'] ?? []);
        $article->tags()->sync($validatedData['tags'] ?? []);

        return redirect()->route('admin.articles.posts.index')->with('success', 'Article post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($postId) // Changed signature
    {
        Log::info("ArticlePostController@destroy: Attempting to delete post with explicit ID: {$postId}");
        try {
            $article = Article::findOrFail($postId);
            Log::info("ArticlePostController@destroy: Successfully fetched Article ID {$article->id} for deletion.");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("ArticlePostController@destroy: Article::findOrFail({$postId}) failed. Post not found for deletion. Redirecting.");
            return redirect()->route('admin.articles.posts.index')->with('error', "Post with ID '{$postId}' not found for deletion.");
        }

        // Consider deleting associated featured image if stored locally
        if ($article->featured_image_path && Storage::disk('public')->exists($article->featured_image_path)) {
            Storage::disk('public')->delete($article->featured_image_path);
        }
        $article->delete();
        return redirect()->route('admin.articles.posts.index')->with('success', 'Article post deleted successfully.');
    }

    public function uploadFeaturedImage(Request $request)
    {
        $request->validate([
            'featured_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Max 2MB
        ]);

        if ($request->hasFile('featured_image')) {
            try {
                $file = $request->file('featured_image');
                $directory = 'uploads/featured_images/' . date('Y/m');
                // Ensure unique filename
                $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                
                // Store the file in the public disk
                $path = $file->storeAs($directory, $filename, 'public');

                // The $path will be like 'uploads/featured_images/2023/10/xxxx.jpg'
                // This path is relative to the 'storage/app/public' directory.
                // When linking, use asset('storage/' . $path)
                
                return response()->json(['success' => true, 'path' => $path, 'url' => Storage::url($path)]); // Use Storage::url() for correct public URL
            } catch (\Exception $e) {
                Log::error('Featured image upload failed: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'No image uploaded.'], 400);
    }
    public function toggleStaffPick(Request $request, $postId)
    {
        try {
            $article = Article::findOrFail($postId);
            $article->staff_pick = !$article->staff_pick;
            $article->save();

            return redirect()->back()->with('success', 'Staff pick status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update staff pick status.');
        }
    }
}
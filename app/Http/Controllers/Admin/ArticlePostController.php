<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveArticleRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Services\ArticleEditorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ArticlePostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Article::with('author', 'categories', 'tags')
            ->latest('updated_at')
            ->paginate(15);

        return view('admin.articles.posts.index', compact('posts'));
    }

    public function create(ArticleEditorService $articleEditorService): View
    {
        return view('admin.articles.posts.create', $this->editorViewData(
            article: new Article(['status' => 'draft']),
            articleEditorService: $articleEditorService
        ));
    }

    public function store(SaveArticleRequest $request, ArticleEditorService $articleEditorService): RedirectResponse
    {
        $article = $articleEditorService->save(
            new Article(),
            $request->validated(),
            $request->user(),
            true,
            'created'
        );

        return redirect()
            ->route('admin.articles.posts.edit', ['post' => $article->id])
            ->with('success', 'Article post created successfully.');
    }

    public function show(Article $article): View
    {
        return view('admin.articles.posts.show', compact('article'));
    }

    public function edit(Article $post, ArticleEditorService $articleEditorService): View
    {
        return view('admin.articles.posts.edit', $this->editorViewData(
            article: $post->load('categories', 'tags', 'revisions.user'),
            articleEditorService: $articleEditorService
        ));
    }

    public function update(
        SaveArticleRequest $request,
        Article $post,
        ArticleEditorService $articleEditorService
    ): RedirectResponse {
        $articleEditorService->save(
            $post,
            $request->validated(),
            $request->user(),
            true,
            'updated'
        );

        return back()->with('success', 'Article post updated successfully.');
    }

    public function destroy(Article $post): RedirectResponse
    {
        if ($post->featured_image_path && Storage::disk('public')->exists($post->featured_image_path)) {
            Storage::disk('public')->delete($post->featured_image_path);
        }

        $post->delete();

        return redirect()->route('admin.articles.posts.index')->with('success', 'Article post deleted successfully.');
    }

    public function uploadFeaturedImage(Request $request)
    {
        $request->validate([
            'featured_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,avif,svg|max:2048',
        ]);

        if ($request->hasFile('featured_image')) {
            try {
                $file = $request->file('featured_image');
                $directory = 'articles';
                $filename = uniqid() . '_' . time();
                $extension = strtolower($file->getClientOriginalExtension());

                if ($extension === 'svg') {
                    $path = $file->storeAs($directory, "{$filename}.svg", 'public');
                } else {
                    $imageManager = new ImageManager(new GdDriver());
                    $image = $imageManager->read($file);
                    $path = "{$directory}/{$filename}.webp";
                    Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(80)));
                }

                return response()->json(['success' => true, 'path' => $path, 'url' => Storage::url($path)]);
            } catch (\Throwable $throwable) {
                Log::error('Featured image upload failed: ' . $throwable->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Upload failed: ' . $throwable->getMessage(),
                ], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'No image uploaded.'], 400);
    }

    public function toggleStaffPick(Request $request, Article $post): RedirectResponse
    {
        $post->staff_pick = !$post->staff_pick;
        $post->save();

        return back()->with('success', 'Featured status updated successfully.');
    }

    private function editorViewData(Article $article, ArticleEditorService $articleEditorService): array
    {
        return [
            'article' => $article,
            'categories' => ArticleCategory::orderBy('name')->get(),
            'tags' => ArticleTag::orderBy('name')->get(),
            'statuses' => $articleEditorService->availableStatuses(Auth::user()),
            'revisions' => $article->exists
                ? $article->revisions()->with('user')->limit(8)->get()
                : collect(),
            'context' => 'admin',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutosaveArticleRequest;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Services\ArticleEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ArticleEditorController extends Controller
{
    public function autosave(AutosaveArticleRequest $request, ArticleEditorService $articleEditorService): JsonResponse
    {
        $article = $request->filled('article_id')
            ? Article::with('categories', 'tags')->findOrFail($request->integer('article_id'))
            : new Article();

        $this->ensureArticleAccess($request->user(), $article, $request->string('context')->toString());

        $validated = $request->validated();

        if (
            !$article->exists
            && blank($validated['title'] ?? null)
            && blank(strip_tags($validated['content'] ?? ''))
            && blank($validated['meta_title'] ?? null)
            && blank($validated['meta_description'] ?? null)
            && empty($validated['categories'] ?? [])
            && empty($validated['tags'] ?? [])
        ) {
            throw ValidationException::withMessages([
                'content' => 'Add a title or some content before autosave can create a draft.',
            ]);
        }

        unset($validated['article_id'], $validated['context']);

        $article = $articleEditorService->autosave($article, $validated, $request->user());

        $context = $request->string('context')->toString();
        $editUrl = $context === 'admin'
            ? route('admin.articles.posts.edit', ['post' => $article->id])
            : route('articles.edit', ['article' => $article->id]);
        $updateUrl = $context === 'admin'
            ? route('admin.articles.posts.update', ['post' => $article->id])
            : route('articles.update', ['article' => $article->id]);

        return response()->json([
            'article_id' => $article->id,
            'edit_url' => $editUrl,
            'update_url' => $updateUrl,
            'preview_url' => route('articles.preview', ['article' => $article->id]),
            'autosaved_at' => $article->updated_at?->toIso8601String(),
            'autosaved_at_label' => optional($article->updated_at)->format('M j, Y g:i A'),
            'current_status' => $article->status,
            'current_published_at' => optional($article->published_at)->format('Y-m-d\TH:i'),
        ]);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif,svg|max:2048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif,svg|max:2048',
        ]);

        $file = $request->file('featured_image') ?? $request->file('image');

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'No image uploaded.'], 400);
        }

        try {
            $directory = 'articles';
            $filename = uniqid('article_', true) . '_' . time();
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === 'svg') {
                $path = $file->storeAs($directory, "{$filename}.svg", 'public');
            } else {
                $imageManager = new ImageManager(new GdDriver());
                $image = $imageManager->read($file);
                $path = "{$directory}/{$filename}.webp";
                Storage::disk('public')->put($path, (string) $image->encode(new WebpEncoder(82)));
            }

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => Storage::url($path),
            ]);
        } catch (\Throwable $throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $throwable->getMessage(),
            ], 500);
        }
    }

    public function preview(Request $request, Article $article)
    {
        $this->ensureArticleAccess($request->user(), $article);

        $article->load('author', 'categories', 'tags');

        return view('articles.show', [
            'post' => $article,
            'isPreview' => true,
        ]);
    }

    public function restoreRevision(
        Request $request,
        ArticleRevision $revision,
        ArticleEditorService $articleEditorService
    ): RedirectResponse {
        $this->ensureArticleAccess($request->user(), $revision->article);

        $articleEditorService->restoreRevision($revision, $request->user());

        return back()->with('success', 'Article restored to the selected revision.');
    }

    private function ensureArticleAccess($user, Article $article, ?string $context = null): void
    {
        abort_unless($user, 403);

        if ($context === 'admin') {
            abort_unless($user->hasRole('admin'), 403);
        }

        if ($user->hasRole('admin')) {
            return;
        }

        abort_unless($article->exists ? $article->user_id === $user->id : true, 403);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveArticleRequest;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Services\ArticleDiscoveryService;
use App\Services\ArticleEditorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function create(ArticleEditorService $articleEditorService): View
    {
        return view('articles.create', $this->editorViewData(
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
            ->route('articles.edit', ['article' => $article->id])
            ->with('success', 'Article saved successfully.');
    }

    public function edit(Article $article, ArticleEditorService $articleEditorService): View
    {
        $this->ensureOwnsArticle($article);

        return view('articles.edit', $this->editorViewData(
            article: $article->load('categories', 'tags', 'revisions.user'),
            articleEditorService: $articleEditorService
        ));
    }

    public function update(
        SaveArticleRequest $request,
        Article $article,
        ArticleEditorService $articleEditorService
    ): RedirectResponse {
        $this->ensureOwnsArticle($article);

        $articleEditorService->save(
            $article,
            $request->validated(),
            $request->user(),
            true,
            'updated'
        );

        return back()->with('success', 'Article updated successfully.');
    }

    public function index(Request $request, ArticleDiscoveryService $articleDiscoveryService): View
    {
        $feed = $request->string('view')->toString() ?: 'latest';

        validator(
            ['view' => $feed],
            ['view' => ['required', Rule::in(['latest', 'featured', 'popular'])]]
        )->validate();

        $posts = match ($feed) {
            'featured' => Article::query()
                ->published()
                ->featured()
                ->with('author', 'categories', 'tags')
                ->latest('published_at')
                ->paginate(10)
                ->withQueryString(),
            'popular' => $articleDiscoveryService->popularArticlesPaginator(10),
            default => Article::query()
                ->published()
                ->with('author', 'categories', 'tags')
                ->latest('published_at')
                ->paginate(10)
                ->withQueryString(),
        };

        return view('articles.index', [
            'posts' => $posts,
            'title' => 'Articles - Software on the web',
            'feed' => $feed,
            'featuredPosts' => $articleDiscoveryService->featuredArticles(),
            'popularPosts' => $articleDiscoveryService->popularArticles(),
            'topicCategories' => $articleDiscoveryService->topicCategories(),
            'mainContentMaxWidth' => 'max-w-4xl',
            'containerMaxWidth' => 'max-w-[90rem]',
        ]);
    }

    public function show(Article $article)
    {
        if ($article->status !== 'published' || $article->published_at > now()) {
            abort(404);
        }

        $article->load('author', 'categories', 'tags');

        return view('articles.show', ['post' => $article]);
    }

    public function category(ArticleCategory $articleCategory)
    {
        $posts = $articleCategory->articles()
            ->published()
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10);

        return view('articles.category', [
            'posts' => $posts,
            'category' => $articleCategory,
        ]);
    }

    public function tag(ArticleTag $articleTag)
    {
        $posts = $articleTag->articles()
            ->published()
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10);

        return view('articles.tag', [
            'posts' => $posts,
            'tag' => $articleTag,
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('query');
        $posts = Article::query()
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10);

        return view('articles.search', ['posts' => $posts, 'query' => $query]);
    }

    public function feed()
    {
        $posts = Article::query()
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(20)
            ->get();

        return response()->view('articles.feed', [
            'posts' => $posts,
        ], 200)->header('Content-Type', 'application/xml');
    }

    public function myArticles()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $articles = $user->articles()->latest('updated_at')->paginate(10);

        return view('articles.my-articles', compact('articles'));
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
            'context' => 'author',
        ];
    }

    private function ensureOwnsArticle(Article $article): void
    {
        abort_unless(Auth::id() === $article->user_id || Auth::user()?->hasRole('admin'), 403);
    }
}

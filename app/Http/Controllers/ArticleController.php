<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use Illuminate\Http\Request;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class ArticleController extends Controller implements Feedable
{
    /**
     * Display a listing of published articles.
     */
    public function index(Request $request)
    {
        $posts = Article::where('status', 'published')
            ->where('published_at', '<=', now())
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10); // Adjust pagination as needed

        $title = 'Articles - Software on the web';

        return view('articles.index', compact('posts', 'title'));
    }

    /**
     * Display the specified article.
     */
    public function show(Article $article) // Route model binding by slug
    {
        // Ensure the post is published and its publication date is not in the future
        if ($article->status !== 'published' || $article->published_at > now()) {
            abort(404);
        }
        $article->load('author', 'categories', 'tags');
        return view('articles.show', ['post' => $article]);
    }

    /**
     * Display posts for a specific category.
     */
    public function category(ArticleCategory $articleCategory) // Route model binding by slug
    {
        $posts = $articleCategory->articles()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10);

        return view('articles.category', ['posts' => $posts, 'articleCategory' => $articleCategory]);
    }

    /**
     * Display posts for a specific tag.
     */
    public function tag(ArticleTag $articleTag) // Route model binding by slug
    {
        $posts = $articleTag->articles()
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10);

        return view('articles.tag', ['posts' => $posts, 'articleTag' => $articleTag]);
    }

    /**
     * Search articles.
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $posts = Article::where('status', 'published')
            ->where('published_at', '<=', now())
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->paginate(10);

        return view('articles.search', ['posts' => $posts, 'query' => $query]);
    }

    /**
     * Generate RSS feed items.
     * Required by Spatie\Feed\Feedable interface.
     */
    public function toFeedItem(): FeedItem
    {
        // This method is usually on the Model (Article in this case)
        // For the controller to be Feedable (if we want a single feed for all posts),
        // this method would need to be adapted or this controller shouldn't implement Feedable directly.
        // Let's assume Article model will implement toFeedItem.
        // This method here is a placeholder if the controller itself was the feed source.
        // It's better to define feeds in config/feed.php and have models implement Feedable.
        // For now, this controller will just have a method to return the feed view.
        // We will configure the feed generation via config/feed.php later.
        return new FeedItem(); // Placeholder
    }

    /**
     * Get all feed items.
     * Required by Spatie\Feed\Feedable interface.
     */
    public static function getFeedItems()
    {
        // This would typically fetch all Article items that should be in the feed.
        return Article::where('status', 'published')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->get();
    }

    /**
     * Display the RSS feed.
     * This will rely on the Spatie Laravel Feed package.
     * The actual feed generation is configured in config/feed.php
     */
    public function feed()
    {
        $posts = Article::where('status', 'published')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit(20)
            ->get();

        return response()->view('articles.feed', [
            'posts' => $posts,
        ], 200)->header('Content-Type', 'application/xml');
    }
}
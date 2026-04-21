<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $articleParam = $this->route('article') ?? $this->route('post');
        $articleId = $articleParam instanceof Article ? $articleParam->id : $articleParam;
        $statuses = $this->user()?->hasRole('admin')
            ? ['draft', 'published', 'scheduled']
            : ['draft', 'published'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'slug')->ignore($articleId),
            ],
            'content' => ['required', 'string'],
            'status' => ['required', Rule::in($statuses)],
            'published_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:65535'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:65535'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'og_url' => ['nullable', 'string', 'max:255', 'url'],
            'twitter_card' => ['nullable', 'string', 'max:255'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:65535'],
            'featured_image_path' => ['nullable', 'string', 'max:255'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', 'exists:article_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:article_tags,id'],
        ];
    }
}

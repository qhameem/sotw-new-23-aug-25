<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutosaveArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $articleId = $this->integer('article_id') ?: null;
        $statuses = $this->user()?->hasRole('admin')
            ? ['draft', 'published', 'scheduled']
            : ['draft', 'published'];

        return [
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'context' => ['required', Rule::in(['author', 'admin'])],
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('articles', 'slug')->ignore($articleId),
            ],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in($statuses)],
            'published_at' => ['nullable', 'date'],
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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:article_categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:article_tags,id'],
        ];
    }
}

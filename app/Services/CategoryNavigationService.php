<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CategoryNavigationService
{
    private const GROUPS = [
        'ai-automation' => [
            'label' => 'AI & Automation',
            'eyebrow' => 'Build With AI',
            'description' => 'LLMs, agents, chatbots, no-code tools, and workflow automation.',
            'icon' => 'brain',
        ],
        'marketing-sales' => [
            'label' => 'Marketing & Sales',
            'eyebrow' => 'Grow Demand',
            'description' => 'SEO, CRM, content, social, analytics, and revenue-driving tools.',
            'icon' => 'megaphone',
        ],
        'productivity-hr' => [
            'label' => 'Productivity & HR',
            'eyebrow' => 'Run The Work',
            'description' => 'Project planning, scheduling, knowledge, hiring, and day-to-day operations.',
            'icon' => 'briefcase',
        ],
        'design-creative' => [
            'label' => 'Design & Creative',
            'eyebrow' => 'Create The Experience',
            'description' => 'Graphic design, UI, media, writing, branding, and creative assets.',
            'icon' => 'palette',
        ],
        'developer-tools' => [
            'label' => 'Developer Tools',
            'eyebrow' => 'Ship Faster',
            'description' => 'APIs, hosting, security, data, frameworks, and developer workflows.',
            'icon' => 'terminal-window',
        ],
        'finance-legal' => [
            'label' => 'Finance & Legal',
            'eyebrow' => 'Protect The Business',
            'description' => 'Accounting, compliance, fintech, legal workflows, and money operations.',
            'icon' => 'bank',
        ],
        'customer-support' => [
            'label' => 'Customer Support',
            'eyebrow' => 'Support Customers',
            'description' => 'Help desks, feedback loops, live chat, and customer communication.',
            'icon' => 'lifebuoy',
        ],
        'view-all' => [
            'label' => 'All Categories',
            'eyebrow' => 'Browse Everything',
            'description' => 'An alphabetical directory of every category, including software, pricing, and best-for tags.',
            'icon' => 'grid-nine',
        ],
    ];

    private const KEYWORDS = [
        'ai-automation' => [
            'ai',
            'agent',
            'automation',
            'llm',
            'prompt',
            'no-code',
            'mcp',
            'vibe coding',
            'robotics',
        ],
        'marketing-sales' => [
            'marketing',
            'sales',
            'crm',
            'seo',
            'newsletter',
            'blog',
            'content',
            'analytics',
            'social media',
            'email',
            'ecommerce',
            'marketplace',
            'directory',
            'community',
            'crowdfunding',
        ],
        'productivity-hr' => [
            'productivity',
            'project management',
            'task management',
            'note taking',
            'knowledge management',
            'habit',
            'journal',
            'time tracking',
            'hiring',
            'human resources',
            'education',
            'learning',
            'event',
            'business',
            'saas',
            'enterprise',
            'freelancing',
            'utility',
            'menu bar',
            'news',
            'travel',
            'health',
            'mental health',
            'lifestyle',
            'wellbeing',
            'dating',
            'other',
        ],
        'design-creative' => [
            'design',
            'creative',
            'ui',
            'audio',
            'image',
            'video',
            'photography',
            'typeface',
            'typography',
            'theme',
            'template',
            'writing',
            'music',
            'fashion',
            'fun',
            'gaming',
        ],
        'developer-tools' => [
            'developer',
            'api',
            'hosting',
            'security',
            'cybersecurity',
            'data',
            'authentication',
            'cloud',
            'framework',
            'github',
            'programming',
            'web development',
            'tailwind',
            'wp plugin',
            'boilerplate',
            'browser',
            'domain',
            'android',
            'ios',
            'macos',
            'windows',
            'visionos',
            'watchos',
            'apple',
            'open source',
            'compression',
            'hardware',
            'wearables',
            'software engineering',
        ],
        'finance-legal' => [
            'finance',
            'legal',
            'compliance',
            'accounting',
            'crypto',
            'blockchain',
            'fintech',
            'climate',
            'solar',
        ],
        'customer-support' => [
            'support',
            'feedback',
            'chat',
            'help desk',
            'customer success',
            'ticket',
        ],
    ];

    private ?Collection $categories = null;

    public function getMenuGroups(): array
    {
        $softwareGroups = $this->buildSoftwareGroups();
        $allCategories = $this->mapCategories($this->categories(), true);

        return collect(self::GROUPS)
            ->map(function (array $group, string $key) use ($softwareGroups, $allCategories) {
                $items = $key === 'view-all' ? $allCategories : ($softwareGroups[$key] ?? collect());

                return [
                    'key' => $key,
                    'label' => $group['label'],
                    'eyebrow' => $group['eyebrow'],
                    'description' => $group['description'],
                    'icon' => $group['icon'],
                    'items' => $items->values()->all(),
                    'item_count' => $items->count(),
                ];
            })
            ->values()
            ->all();
    }

    public function getDefaultGroupKey(): string
    {
        return 'ai-automation';
    }

    private function categories(): Collection
    {
        if ($this->categories !== null) {
            return $this->categories;
        }

        $today = Carbon::today();

        return $this->categories = Category::query()
            ->with('types')
            ->withCount([
                'products' => function ($query) use ($today) {
                    $query->where('approved', true)
                        ->where(function ($subQuery) use ($today) {
                            $subQuery->whereNull('published_at')
                                ->orWhereDate('published_at', '<=', $today);
                        });
                },
            ])
            ->orderBy('name')
            ->get();
    }

    private function buildSoftwareGroups(): Collection
    {
        $grouped = collect(array_keys(self::GROUPS))
            ->reject(fn (string $key) => $key === 'view-all')
            ->mapWithKeys(fn (string $key) => [$key => collect()]);

        foreach ($this->softwareCategories() as $category) {
            $key = $this->resolveGroupKey($category->name);
            $grouped[$key]->push($this->mapCategory($category));
        }

        return $grouped->map(fn (Collection $items) => $items->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values());
    }

    private function softwareCategories(): Collection
    {
        return $this->categories()
            ->filter(function (Category $category) {
                $typeNames = $category->types
                    ->pluck('name')
                    ->map(fn ($name) => strtolower((string) $name));

                return $typeNames->contains(fn (string $name) => str_contains($name, 'software'));
            })
            ->values();
    }

    private function mapCategories(Collection $categories, bool $includeTypeLabel = false): Collection
    {
        return $categories
            ->map(fn (Category $category) => $this->mapCategory($category, $includeTypeLabel))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function mapCategory(Category $category, bool $includeTypeLabel = false): array
    {
        $typeLabel = $category->types->first()?->name;

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'url' => route('categories.show', ['category' => $category->slug]),
            'count' => (int) $category->products_count,
            'type_label' => $includeTypeLabel ? $typeLabel : null,
        ];
    }

    private function resolveGroupKey(string $name): string
    {
        $normalized = strtolower($name);

        foreach (self::KEYWORDS as $groupKey => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $groupKey;
                }
            }
        }

        return 'productivity-hr';
    }
}

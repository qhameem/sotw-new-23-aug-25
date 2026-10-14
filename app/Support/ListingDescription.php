<?php

namespace App\Support;

class ListingDescription
{
    public const LIST_LIMITS = ['features' => 6, 'top_use_cases' => 4, 'comparisons' => 3, 'integrations' => 4, 'pros' => 4];

    public static function fields(array $body): array
    {
        return [
            'summary' => $body['summary'],
            'features' => $body['features'],
            'best_for_text' => $body['best_for'],
            'not_for_text' => $body['not_for'],
            'faq' => $body['faq'],
            ...array_replace(
                array_fill_keys(['top_use_cases', 'comparisons', 'integrations', 'pros', 'editorial_qa'], []),
                array_intersect_key($body, array_flip(['top_use_cases', 'comparisons', 'integrations', 'pros', 'editorial_qa']))
            ),
            'description' => self::html($body),
        ];
    }

    public static function html(array $body): string
    {
        $html = '<p>'.e($body['summary']).'</p>';
        foreach (['features' => 'Key Features', 'best_for' => 'Best For', 'top_use_cases' => 'Top Use Cases', 'comparisons' => 'Comparisons', 'integrations' => 'Integrations', 'pros' => 'Pros', 'not_for' => 'Limitations'] as $key => $title) {
            $items = is_array($body[$key] ?? null) ? $body[$key] : array_filter([$body[$key] ?? '']);
            if ($items === []) {
                continue;
            }
            $html .= '<h2>'.e($title).'</h2><ul>';
            foreach ($items as $item) {
                $html .= '<li>'.e($item).'</li>';
            }
            $html .= '</ul>';
        }
        foreach ($body['editorial_qa'] ?? [] as $item) {
            $html .= '<h3>'.e($item['question']).'</h3><p>'.e($item['answer']).'</p>';
        }

        return $html;
    }
}

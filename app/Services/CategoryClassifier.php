<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryClassifier
{
    public function classify(string $text, int $topN = 3, string $type = 'Software'): array
    {
        $text = $this->preprocess($text);
        $tokens = $this->tokenize($text);

        $categories = Category::whereHas('types', function ($query) use ($type) {
            $query->where('name', $type);
        })->whereNotNull('keywords')->get();
        
        $categoryScores = [];

        foreach ($categories as $category) {
            $keywords = json_decode($category->keywords, true);
            if (empty($keywords)) {
                continue;
            }

            $matches = count(array_intersect($keywords, $tokens));
            $score = $matches / count($keywords);

            if ($score > 0) {
                $categoryScores[$category->name] = $score;
            }
        }

        arsort($categoryScores);

        return array_slice($categoryScores, 0, $topN, true);
    }

    private function preprocess(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        return $text;
    }

    private function tokenize(string $text): array
    {
        return explode(' ', $text);
    }
}
<?php

namespace App\Services;

class OutputValidator
{
    private const BAD_ENDINGS = ['and', 'or', 'with', 'for', 'to', 'of', 'in', 'the', 'a', 'an'];

    public function text(string $field, string $value, string $source = ''): array
    {
        $errors = [];
        $length = mb_strlen($value);
        $limits = ['tagline' => [40, 70], 'seo_title' => [50, 60], 'meta_description' => [140, 155]];
        if (isset($limits[$field]) && ($length < $limits[$field][0] || $length > $limits[$field][1])) {
            $errors[] = "$field must be {$limits[$field][0]} to {$limits[$field][1]} characters; got $length.";
        }
        if ($field === 'summary') {
            $count = count(preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY));
            if ($count < 40 || $count > 60) {
                $errors[] = "summary must be 40 to 60 words; got $count.";
            }
        }
        if (in_array($field, ['best_for', 'not_for'], true) && $length > 255) {
            $errors[] = "$field must be at most 255 characters.";
        }
        if ($field === 'meta_description' && preg_match_all('/[.!?](?:\s|$)/u', $value) !== 2) {
            $errors[] = 'meta_description must contain two complete sentences.';
        }
        if (preg_match('/\b('.implode('|', self::BAD_ENDINGS).')\W*$/iu', $value)) {
            $errors[] = "$field ends on an incomplete word.";
        }
        foreach (config('listing_generation.banned_words', []) as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/iu', $value)) {
                $errors[] = "$field contains banned word: $word.";
            }
        }
        if (preg_match('/[—;*#]|[\x{1F300}-\x{1FAFF}]|<[^>]+>|\[[^]]+\]\([^)]+\)/u', $value)) {
            $errors[] = "$field contains forbidden punctuation, emoji, or markup.";
        }
        if ($source !== '' && $this->sharesEightWords($value, $source)) {
            $errors[] = "$field repeats eight consecutive source words.";
        }

        return $errors;
    }

    public function schema(string $kind, ?array $value): array
    {
        if ($value === null) {
            return ["$kind must be valid JSON object."];
        }
        $shapes = [
            'tagline' => ['candidates' => 'array'],
            'description' => ['summary' => 'string', 'features' => 'array', 'best_for' => 'string', 'not_for' => 'string', 'faq' => 'array'],
            'seo' => ['seo_title' => 'string', 'meta_description' => 'string', 'seo_title_length' => 'integer', 'meta_description_length' => 'integer'],
            'verification' => ['issues' => 'array'],
        ];
        $errors = [];
        foreach ($shapes[$kind] ?? [] as $key => $type) {
            if (! array_key_exists($key, $value) || gettype($value[$key]) !== $type) {
                $errors[] = "$kind.$key must be $type.";
            }
        }
        if ($errors !== []) return $errors;
        if ($kind === 'tagline' && (count($value['candidates']) !== 3 || count(array_filter($value['candidates'], 'is_string')) !== 3)) {
            $errors[] = 'tagline.candidates must contain three strings.';
        }
        if ($kind === 'description') {
            if (count(array_filter($value['features'], 'is_string')) !== count($value['features'])) $errors[] = 'description.features must contain strings.';
            foreach ($value['faq'] as $item) {
                if (! is_array($item) || ! is_string($item['question'] ?? null) || ! is_string($item['answer'] ?? null)) {
                    $errors[] = 'description.faq items need question and answer strings.';
                    break;
                }
            }
        }
        if ($kind === 'verification') {
            foreach ($value['issues'] as $item) {
                if (! is_array($item) || ! is_string($item['field'] ?? null) || ! is_string($item['problem'] ?? null)) {
                    $errors[] = 'verification.issues items need field and problem strings.';
                    break;
                }
            }
        }

        return $errors;
    }

    private function sharesEightWords(string $value, string $source): bool
    {
        $words = fn ($s) => preg_split('/[^\pL\pN]+/u', mb_strtolower($s), -1, PREG_SPLIT_NO_EMPTY);
        $generated = $words($value);
        $original = ' '.implode(' ', $words($source)).' ';
        for ($i = 0; $i <= count($generated) - 8; $i++) {
            if (str_contains($original, ' '.implode(' ', array_slice($generated, $i, 8)).' ')) {
                return true;
            }
        }

        return false;
    }
}

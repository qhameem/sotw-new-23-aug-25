<?php

namespace App\Services;

class PricingEvidenceService
{
    public function extract(string $source): array
    {
        $evidence = ['monthly_subscription' => [], 'yearly_subscription' => [], 'free_plan' => [], 'paid_plan' => []];
        $blocks = preg_split('/(?=^Source: )/m', $source, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($blocks as $block) {
            preg_match('/^Source: (\S+)/', $block, $url);
            $patterns = [
                'monthly_subscription' => '~(?:[$€£]\s*|\b(?:USD|EUR|GBP)\s+)(\d+(?:[.,]\d+)?)\s*(?:/\s*|per\s+)(?:month|mo)\b~iu',
                'yearly_subscription' => '~(?:[$€£]\s*|\b(?:USD|EUR|GBP)\s+)(\d+(?:[.,]\d+)?)\s*(?:/\s*|per\s+)(?:year|yr)\b~iu',
                'free_plan' => '~\bfree\s+(?:plan|tier|mode)\b~iu',
                'paid_plan' => '~\bpaid\s+(?:plan|tier|subscription)s?\b~iu',
            ];
            foreach ($patterns as $kind => $pattern) {
                preg_match_all($pattern, $block, $matches, PREG_OFFSET_CAPTURE);
                foreach ($matches[0] as $index => [$quote, $offset]) {
                    if (str_ends_with($kind, '_subscription') && (float) str_replace(',', '.', $matches[1][$index][0]) <= 0) {
                        continue;
                    }
                    // Keep the original surrounding text so claims can be reviewed in context.
                    $evidence[$kind][] = ['quote' => $quote, 'context' => mb_strcut($block, max(0, $offset - 80), strlen($quote) + 160, 'UTF-8'), 'source_url' => $url[1] ?? null];
                }
            }
        }

        return $evidence;
    }

    public function supportingQuotes(string $category, array $evidence): array
    {
        return match (mb_strtolower(trim($category))) {
            'subscription' => array_merge($evidence['monthly_subscription'], $evidence['yearly_subscription']),
            'monthly subscription' => $evidence['monthly_subscription'],
            'annual subscription', 'yearly subscription' => $evidence['yearly_subscription'],
            'freemium' => $evidence['free_plan'] !== [] && ($evidence['paid_plan'] !== [] || $evidence['monthly_subscription'] !== [] || $evidence['yearly_subscription'] !== [])
                ? array_merge($evidence['free_plan'], $evidence['paid_plan'], $evidence['monthly_subscription'], $evidence['yearly_subscription']) : [],
            default => [],
        };
    }
}

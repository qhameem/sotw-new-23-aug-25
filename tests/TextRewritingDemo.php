<?php

/**
 * Demo of text rewriting techniques without using external AI APIs
 * This file demonstrates various approaches to rewrite product descriptions and taglines
 */

class TextRewritingDemo
{
    /**
     * Simple extractive summarization - picks the most important sentences
     */
    public function extractiveSummarize(string $text, int $numSentences = 2): string
    {
        // Split text into sentences
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_map('trim', $sentences);
        $sentences = array_filter($sentences, function($s) {
            return strlen($s) > 10; // Filter out very short sentences
        });

        if (empty($sentences)) {
            return $text;
        }

        // Score sentences based on length and keyword density
        $sentenceScores = [];
        foreach ($sentences as $sentence) {
            $score = strlen($sentence); // Longer sentences often contain more information
            // Add points for important keywords (could be extracted from product name)
            $score += (substr_count(strtolower($sentence), 'powerful') * 5);
            $score += (substr_count(strtolower($sentence), 'easy') * 3);
            $score += (substr_count(strtolower($sentence), 'best') * 4);
            $score += (substr_count(strtolower($sentence), 'feature') * 3);
            $sentenceScores[] = $score;
        }

        // Create array of sentence-score pairs
        $sentenceScorePairs = array_combine($sentences, $sentenceScores);
        
        // Sort by score (descending)
        arsort($sentenceScorePairs);

        // Take top sentences
        $topSentences = array_slice($sentenceScorePairs, 0, min($numSentences, count($sentenceScorePairs)), true);
        
        return implode('. ', array_keys($topSentences)) . '.';
    }

    /**
     * Template-based rewriting - restructures content using predefined templates
     */
    public function templateRewrite(string $productName, string $originalDescription): string
    {
        $templates = [
            "Discover {$productName}, a powerful solution that helps users achieve more with less effort.",
            "{$productName} is the ultimate tool for those looking to streamline their workflow.",
            "Transform your approach with {$productName}, designed for efficiency and simplicity.",
            "Experience the difference with {$productName}, engineered for modern professionals.",
            "{$productName} brings innovation and usability together in one comprehensive package."
        ];

        // Pick a random template or one that best matches keywords in the original description
        $keywords = ['powerful', 'easy', 'best', 'solution', 'tool', 'professional', 'innovative'];
        $keywordCounts = [];
        
        foreach ($templates as $template) {
            $count = 0;
            foreach ($keywords as $keyword) {
                if (stripos($originalDescription, $keyword) !== false) {
                    $count++;
                }
            }
            $keywordCounts[] = $count;
        }

        // Select template with highest keyword match, or random if all are equal
        $maxIndex = array_search(max($keywordCounts), $keywordCounts);
        
        return $templates[$maxIndex];
    }

    /**
     * Keyword extraction and rephrasing
     */
    public function keywordRephrase(string $originalText, string $productName): string
    {
        // Extract keywords (simplified approach)
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'may', 'might', 'must', 'can', 'could'];
        
        $words = preg_split('/\s+/', preg_replace('/[^\w\s]/', ' ', strtolower($originalText)));
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array(strtolower($word), $stopWords) && strlen($word) > 2;
        });
        
        // Count word frequency
        $wordFreq = array_count_values($words);
        arsort($wordFreq);
        
        // Get top 5 keywords
        $topKeywords = array_slice(array_keys($wordFreq), 0, 5);
        
        // Create a new sentence using the product name and keywords
        $newDescription = "Introducing {$productName}, featuring " . implode(', ', $topKeywords) . ". This innovative solution provides exceptional value for users.";
        
        return $newDescription;
    }

    /**
     * Rule-based text transformation
     */
    public function ruleBasedRewrite(string $originalText): string
    {
        // Apply various transformations
        $rewritten = $originalText;
        
        // Replace common phrases with alternatives
        $replacements = [
            '/(is|are|was|were) a (powerful|great|amazing|fantastic)/i' => 'represents a powerful',
            '/(allows|helps|enables) you to/i' => 'empowers users to',
            '/(easy|simple) to (use|implement|understand)/i' => 'designed for effortless use',
            '/(best|top) (solution|option|choice)/i' => 'premium solution',
            '/designed for (beginners|professionals|everyone)/i' => 'engineered specifically for $1',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $rewritten = preg_replace($pattern, $replacement, $rewritten);
        }
        
        // Ensure it's not too long
        if (strlen($rewritten) > 200) {
            $sentences = preg_split('/[.!?]+/', $rewritten);
            $result = '';
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (strlen($result . $sentence . '. ') <= 200) {
                    $result .= $sentence . '. ';
                } else {
                    break;
                }
            }
            $rewritten = rtrim($result);
       }
       
       return $rewritten;
   }

   /**
    * Generate multiple variations of a tagline
    */
   public function generateTaglineVariations(string $productName, string $originalTagline, int $limit = 3): array
    {
        $variations = [];
        
        // Extract key concepts from original tagline
        $words = explode(' ', preg_replace('/[^\w\s]/', '', $originalTagline));
        $words = array_filter($words, function($word) {
            return strlen($word) > 3;
        });
        
        // Generate different styles
        $templates = [
            "The #1 {$words[array_rand($words)]} solution for {$productName}",
            "{$productName}: Revolutionizing {$words[array_rand($words)]}",
            "Experience {$productName} - {$words[array_rand($words)]} redefined",
            "{$productName}: Where {$words[array_rand($words)]} meets innovation",
            "Transform your {$words[array_rand($words)]} with {$productName}",
        ];
        
        // Limit to requested number
        for ($i = 0; $i < min($limit, count($templates)); $i++) {
            $variations[] = substr($templates[array_rand($templates)], 0, 60);
        }
        
        // Add original as variation if needed
        if (count($variations) < $limit) {
            $variations[] = substr($originalTagline, 0, 60);
        }
        
        return array_slice($variations, 0, $limit);
    }
}

// Demo usage
$demo = new TextRewritingDemo();

// Sample product data
$productName = "TaskMaster Pro";
$originalDescription = "TaskMaster Pro is an easy-to-use project management tool that helps teams collaborate effectively. It features powerful task tracking, time management, and team communication tools. Perfect for professionals who need to streamline their workflow and increase productivity.";
$originalTagline = "Powerful project management made simple";

echo "=== ORIGINAL TEXT ===\n";
echo "Product: $productName\n";
echo "Original Description: $originalDescription\n";
echo "Original Tagline: $originalTagline\n\n";

echo "=== EXTRACTIVE SUMMARIZATION ===\n";
echo "Rewritten Description: " . $demo->extractiveSummarize($originalDescription, 2) . "\n\n";

echo "=== TEMPLATE-BASED REWRITING ===\n";
echo "Rewritten Description: " . $demo->templateRewrite($productName, $originalDescription) . "\n\n";

echo "=== KEYWORD EXTRACTION & REPHRASING ===\n";
echo "Rewritten Description: " . $demo->keywordRephrase($originalDescription, $productName) . "\n\n";

echo "=== RULE-BASED REWRITING ===\n";
echo "Rewritten Description: " . $demo->ruleBasedRewrite($originalDescription) . "\n\n";

echo "=== TAGLINE VARIATIONS ===\n";
$taglineVariations = $demo->generateTaglineVariations($productName, $originalTagline, 5);
foreach ($taglineVariations as $i => $variation) {
    echo ($i + 1) . ". $variation\n";
}
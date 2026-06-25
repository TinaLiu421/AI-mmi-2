<?php

namespace App\Services;

use App\Models\ImmigrationDocument;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ImmigrationRagService
{
    private Client $client;
    private array $sourceDomains = [
        'australia' => [
            'url' => 'https://immi.homeaffairs.gov.au/visas/getting-a-visa/visa-finder',
            'title' => 'Australian Visa Finder',
        ],
        'nz_business' => [
            'url' => 'https://www.immigration.govt.nz/visas/business-investor-work-visa/',
            'title' => 'NZ Business/Investor/Work Visas',
        ],
        'nz_main' => [
            'url' => 'https://www.immigration.govt.nz',
            'title' => 'NZ Immigration Main',
        ],
    ];

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
    }

    /**
     * Scrape and ingest immigration documents
     */
    public function ingestAllSources(): array
    {
        $results = [];
        
        foreach ($this->sourceDomains as $key => $source) {
            try {
                Log::info("Ingesting immigration document: {$key}");
                $country = strpos($key, 'nz') === 0 ? 'nz' : 'australia';
                $section = $key;
                
                $result = $this->scrapeAndStore($source['url'], $country, $section, $source['title']);
                $results[$key] = $result;
            } catch (\Exception $e) {
                Log::error("Failed to ingest {$key}: " . $e->getMessage());
                $results[$key] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Scrape a single URL and store in database
     */
    private function scrapeAndStore(string $url, string $country, string $section, string $title): array
    {
        try {
            // Fetch HTML
            $response = $this->client->get($url);
            $html = (string)$response->getBody();
            
            // Extract main content using simple HTML parsing
            $content = $this->extractContent($html);
            $contentClean = $this->cleanContent($content);
            
            if (empty($contentClean)) {
                return ['status' => 'empty', 'url' => $url];
            }
            
            // Check if document already exists
            $existing = ImmigrationDocument::where('source_url', $url)->first();
            if ($existing) {
                $existing->update([
                    'content' => $content,
                    'content_clean' => $contentClean,
                    'title' => $title,
                    'word_count' => str_word_count($contentClean),
                ]);
                return ['status' => 'updated', 'url' => $url, 'words' => str_word_count($contentClean)];
            }
            
            // Create new document
            $doc = ImmigrationDocument::create([
                'country' => $country,
                'source_url' => $url,
                'title' => $title,
                'content' => $content,
                'content_clean' => $contentClean,
                'section' => $section,
                'keywords' => implode(',', $this->extractKeywords($contentClean)),
                'word_count' => str_word_count($contentClean),
            ]);
            
            return ['status' => 'created', 'url' => $url, 'id' => $doc->id, 'words' => $doc->word_count];
        } catch (\Exception $e) {
            return ['status' => 'error', 'url' => $url, 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract text content from HTML using simple DOM parsing
     */
    private function extractContent(string $html): string
    {
        // Remove script and style tags first
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', ' ', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', ' ', $html);
        $html = preg_replace('/<nav[^>]*>.*?<\/nav>/is', ' ', $html);
        $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', ' ', $html);
        $html = preg_replace('/<header[^>]*>.*?<\/header>/is', ' ', $html);
        
        // Try to extract from main content areas
        if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $m)) {
            $content = $m[1];
        } elseif (preg_match('/<article[^>]*>(.*?)<\/article>/is', $html, $m)) {
            $content = $m[1];
        } elseif (preg_match('/<div[^>]*class=["\']?content["\']?[^>]*>(.*?)<\/div>/is', $html, $m)) {
            $content = $m[1];
        } elseif (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $m)) {
            $content = $m[1];
        } else {
            $content = $html;
        }
        
        // Remove all remaining tags
        $text = strip_tags($content);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($text);
    }

    /**
     * Clean content: remove extra whitespace, normalize
     */
    private function cleanContent(string $content): string
    {
        // Remove multiple spaces/newlines
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Remove common non-content patterns
        $content = preg_replace('/\b(follow us|share|subscribe|contact us|phone|email)\b/i', '', $content);
        
        // Limit to 50k chars
        $content = trim(substr($content, 0, 50000));
        
        return $content;
    }

    /**
     * Extract important keywords from content
     */
    private function extractKeywords(string $content): array
    {
        $words = preg_split('/[\s,\.;!?]+/', strtolower($content), -1, PREG_SPLIT_NO_EMPTY);
        
        // Filter by frequency and length
        $wordFreq = array_count_values($words);
        
        $keywords = [];
        foreach ($wordFreq as $word => $count) {
            if (strlen($word) > 4 && $count >= 2) { // Word appears at least twice
                $keywords[$word] = $count;
            }
        }
        
        // Sort by frequency and take top 20
        arsort($keywords);
        return array_slice(array_keys($keywords), 0, 20);
    }

    /**
     * Search for relevant immigration documents
     */
    public function searchRelevant(string $query, string $country = null, int $limit = 3): array
    {
        try {
            // Extract keywords from query
            $keywords = ImmigrationDocument::extractKeywords($query);
            
            if (empty($keywords)) {
                // Fallback: search raw query
                return ImmigrationDocument::searchByKeywords($query, $country, $limit)->toArray();
            }
            
            // Build search query
            $searchQuery = implode(' ', $keywords);
            
            // Add common immigration terms to boost results
            $boostedTerms = $this->detectImmigrationTerms($query);
            if (!empty($boostedTerms)) {
                $searchQuery .= ' ' . implode(' ', $boostedTerms);
            }
            
            return ImmigrationDocument::searchByKeywords($searchQuery, $country, $limit)->toArray();
        } catch (\Exception $e) {
            Log::error("Immigration document search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detect immigration-related terms in query
     */
    private function detectImmigrationTerms(string $query): array
    {
        $terms = [
            'visa' => 'visa',
            'work' => 'work',
            'business' => 'business',
            'investor' => 'investor',
            'skilled' => 'skilled',
            'requirement' => 'requirement',
            'eligible' => 'eligible',
            'application' => 'application',
            'spouse' => 'spouse',
            'partner' => 'partner',
            'family' => 'family',
            'immigration' => 'immigration',
            'residency' => 'residency',
            'resident' => 'resident',
            'points' => 'points',
            'criteria' => 'criteria',
            'fee' => 'fee',
            'document' => 'document',
        ];
        
        $found = [];
        $queryLower = strtolower($query);
        
        foreach ($terms as $term => $value) {
            if (strpos($queryLower, $term) !== false) {
                $found[] = $value;
            }
        }
        
        return $found;
    }

    /**
     * Format documents for injection into Grok context
     */
    public function formatDocumentsForContext(array $documents, int $maxLength = 2000): string
    {
        if (empty($documents)) {
            return '';
        }
        
        $formatted = "📋 **Immigration Reference Documents:**\n\n";
        $currentLength = strlen($formatted);
        
        foreach ($documents as $doc) {
            $excerpt = substr($doc['content_clean'] ?? '', 0, 400);
            if (strlen($excerpt) > 200) {
                $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')) . '...';
            }
            
            $section = "**{$doc['title']}** ({$doc['country']})\n{$excerpt}\n\n";
            
            if ($currentLength + strlen($section) > $maxLength) {
                break;
            }
            
            $formatted .= $section;
            $currentLength += strlen($section);
        }
        
        return $formatted;
    }

    /**
     * Check if query is immigration-related
     */
    public function isImmigrationQuery(string $query): bool
    {
        $keywords = [
            'visa', 'immigration', 'work permit', 'residency', 'migrant',
            'skilled migration', 'sponsorship', 'points test',
            'application', 'eligible', 'requirement', 'australia', 'new zealand',
            'business visa', 'investor visa', 'work visa', 'family visa',
        ];
        
        $queryLower = strtolower($query);
        
        foreach ($keywords as $keyword) {
            if (strpos($queryLower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

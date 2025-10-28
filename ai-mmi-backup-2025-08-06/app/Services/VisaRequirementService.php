<?php

namespace App\Services;

use App\Services\Rag\Embeddings;
use App\Services\Rag\Pinecone;
use App\Services\Rag\Generator;
use Illuminate\Support\Facades\Log;

class VisaRequirementService
{
    private Embeddings $embeddings;
    private Pinecone $pinecone;
    private Generator $generator;

    public function __construct(Embeddings $embeddings, Pinecone $pinecone, Generator $generator)
    {
        $this->embeddings = $embeddings;
        $this->pinecone = $pinecone;
        $this->generator = $generator;
    }

    /**
     * Get clean visa requirements for a specific visa type from RAG
     * Uses Gemini to extract and format requirements, with regex fallback
     *
     * @param string $visaType The visa type (e.g., "Temporary Graduate")
     * @param string|null $tag Optional filter tag (default: "visa")
     * @return array Result with 'found', 'criteria', and metadata
     */
    public function getVisaRequirements(string $visaType, ?string $tag = "visa"): array
    {
        try {
            // Build query and search RAG
            $query = sprintf("What are the requirements for %s visa?", $visaType);
            $queryVector = $this->embeddings->embed($query);
            $filter = $tag ? ['tag' => $tag] : null;
            $matches = $this->pinecone->query($queryVector, 20, $filter);

            if (empty($matches)) {
                return [
                    'visa_type' => $visaType,
                    'found' => false,
                    'message' => 'No visa requirements found',
                    'criteria' => []
                ];
            }

            // Combine all RAG context
            $contexts = array_filter(array_map(fn($m) => $m['metadata']['content'] ?? '', $matches));
            $context = implode("\n---\n", $contexts);

            // Try Gemini cleaning first
            $criteria = $this->cleanRequirementsWithGemini($context, $visaType);

            if (!empty($criteria)) {
                return [
                    'visa_type' => $visaType,
                    'found' => true,
                    'criteria' => $criteria,
                    'source_matches' => count($matches),
                    'cleaned_by' => 'gemini'
                ];
            }

            // Fallback to regex-based cleaning
            Log::info('Gemini cleaning failed, using regex fallback', ['visa_type' => $visaType]);
            $criteria = $this->cleanRequirementsWithRegex($context);

            if (!empty($criteria)) {
                return [
                    'visa_type' => $visaType,
                    'found' => true,
                    'criteria' => $criteria,
                    'source_matches' => count($matches),
                    'cleaned_by' => 'regex_fallback'
                ];
            }

            // No requirements could be extracted
            return [
                'visa_type' => $visaType,
                'found' => false,
                'message' => 'Unable to parse requirements',
                'criteria' => []
            ];

        } catch (\Throwable $e) {
            Log::error('VisaRequirementService::getVisaRequirements failed', [
                'visa_type' => $visaType,
                'error' => $e->getMessage()
            ]);

            return [
                'visa_type' => $visaType,
                'found' => false,
                'error' => $e->getMessage(),
                'criteria' => []
            ];
        }
    }

    /**
     * Get multiple visa requirements at once
     *
     * @param array $visaTypes Array of visa types
     * @param string|null $tag Optional filter tag
     * @return array
     */
    public function getMultipleVisaRequirements(array $visaTypes, ?string $tag = "visa"): array
    {
        $results = [];
        foreach ($visaTypes as $visaType) {
            $results[$visaType] = $this->getVisaRequirements($visaType, $tag);
        }
        return $results;
    }

    /**
     * Use Gemini API to extract clean, structured requirements from RAG context
     * Prioritizes conciseness and clarity
     *
     * @param string $context Combined RAG content
     * @param string $visaType Visa type for context
     * @return array Requirements or empty array if Gemini fails
     */
    private function cleanRequirementsWithGemini(string $context, string $visaType): array
    {
        try {
            // Truncate context to avoid token limits
            $context = mb_strimwidth($context, 0, 8000, '...');

            $prompt = <<<PROMPT
You are a visa requirements expert. Extract core eligibility requirements from this visa information.

VISA: $visaType

RAW TEXT:
$context

TASK: Identify ESSENTIAL requirements (not background/descriptions)

OUTPUT FORMAT - Return ONLY valid JSON:
{
  "requirements": [
    {
      "name": "Requirement (6-10 words)",
      "description": "What it means (25-50 words)"
    }
  ]
}

Rules:
- Names must be concise and specific (6-10 words max)
- Descriptions must be simple and direct (25-50 words max)
- Remove duplicates and generic text
- Maximum 10 requirements
- NO markdown or code blocks in response
PROMPT;

            $apiKey = env('GEMINI_API_KEY');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $apiKey;

            $data = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 2048]
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                Log::warning('Gemini API error', ['status' => $httpCode]);
                return [];
            }

            // Parse Gemini response
            $result = json_decode($response, true);
            $geminiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

            // Extract JSON from response
            if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $geminiText, $m)) {
                $geminiText = $m[1];
            } elseif (preg_match('/(\{.*\})/s', $geminiText, $m)) {
                $geminiText = $m[0];
            }

            $parsed = json_decode($geminiText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse Gemini JSON');
                return [];
            }

            // Build requirements from Gemini response
            $requirements = [];
            foreach ($parsed['requirements'] ?? [] as $req) {
                $name = trim($req['name'] ?? '');
                $desc = trim($req['description'] ?? '');

                if (!empty($name) && !empty($desc)) {
                    $requirements[] = [
                        'name' => $name,
                        'description' => $desc,
                        'status' => 'pending'
                    ];
                }
            }

            if (!empty($requirements)) {
                Log::info('Gemini cleaned requirements', ['count' => count($requirements)]);
            }

            return $requirements;

        } catch (\Throwable $e) {
            Log::warning('cleanRequirementsWithGemini error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Clean requirements using smart regex patterns (fallback when Gemini unavailable)
     * Uses intelligent pattern matching to extract key visa requirements
     *
     * @param string $context Combined RAG content
     * @return array Cleaned requirements
     */
    private function cleanRequirementsWithRegex(string $context): array
    {
        $requirements = [];
        $seenCategories = [];

        // Clean context first
        $context = preg_replace('/===== Page \d+ =====/i', '', $context);
        $context = preg_replace('/:\s*contentReference.+?\{index=\d+\}/i', '', $context);
        $context = preg_replace('/\s+/', ' ', $context);

        // Define requirement categories with patterns (country-agnostic)
        // Key: category (for deduplication), Value: [pattern, friendly name, priority]
        // Higher priority = checked first to avoid conflicts
        $requirementPatterns = [
            'english' => [
                '/(?:IELTS|TOEFL|PTE|English\s+(?:language|proficiency)).*?(?:\d\.\d|score|proficiency|test|requirement)/i',
                'Meet English language proficiency (IELTS/TOEFL/PTE)',
                10
            ],
            'education' => [
                '/(?:degree|bachelor|master|qualification|diploma|phd|university\s+degree)(?:.*?)(?:from|awarded|completed|hold)/i',
                'Hold relevant degree or qualification',
                10
            ],
            'recent_completion' => [
                '/(?:recent|within\s+\d+\s+months?|6\s+months?|12\s+months?).*?(?:completed|graduation|degree|qualification|studied)/i',
                'Recently completed qualification (within timeframe)',
                9
            ],
            'experience' => [
                '/(?:\d+\s+)?years?\s+(?:of\s+)?(?:work|experience|working|professional)(?:.*?)(?:experience|employment)/i',
                'Have required work experience',
                9
            ],
            'skills' => [
                '/(?:skills?|occupation|profession|trade).*?(?:relevant|related|nominated|required|skilled)/i',
                'Have required skills or occupation',
                8
            ],
            'age' => [
                '/(?:under|maximum|minimum|age\s+limit).*?(?:\d+).*?(?:years?|yrs?)/i',
                'Meet age requirements',
                8
            ],
            'character' => [
                '/(?:character|police|criminal|conviction|health|medical|examination|assessment)(?:.*?)(?:pass|meet|requirement)/i',
                'Pass character and health assessments',
                7
            ],
            'visa_timing' => [
                '/(?:within|must\s+apply|application\s+deadline).*?(?:\d+\s+)?(?:months?|days?|weeks?).*?(?:completion|graduation|qualification)/i',
                'Apply within specified timeframe of graduation',
                7
            ],
            'not_previous' => [
                '/(?:cannot|cannot\s+be|not\s+eligible|previously|prior|earlier).*?(?:held|granted|obtained).*?(?:visa|subclass|category)/i',
                'Check previous visa restrictions',
                6
            ],
            'location_requirement' => [
                '/(?:must\s+be|be\s+in|located\s+in|reside\s+in).*?(?:country|location).*?(?:when\s+apply|at\s+application|submission)/i',
                'Meet location requirements for application',
                6
            ],
            'work_rights' => [
                '/(?:work|employment|full-time|part-time).*?(?:hours?|weeks?|permitted|allowed|restrictions|conditions)/i',
                'Understand work rights and conditions',
                5
            ],
            'dependents' => [
                '/(?:family|spouse|partner|dependant|children|dependent).*?(?:eligible|accompany|include|application)/i',
                'Family members may be eligible to apply',
                5
            ],
            'income_financial' => [
                '/(?:income|salary|financial|sponsor|funds|proof\s+of).*?(?:requirement|minimum|sufficient|assets)/i',
                'Meet financial or income requirements',
                5
            ]
        ];

        // Sort by priority (higher first)
        uasort($requirementPatterns, function($a, $b) {
            return $b[2] <=> $a[2];
        });

        // Try to find matches for each category
        foreach ($requirementPatterns as $category => $patternData) {
            list($pattern, $friendlyName) = $patternData;

            // Skip if we already have this category
            if (isset($seenCategories[$category])) {
                continue;
            }

            if (preg_match($pattern, $context, $matches)) {
                $seenCategories[$category] = true;

                // Extract descriptive text
                $description = $this->extractContextDescription($context, $matches[0]);

                $requirements[] = [
                    'name' => $friendlyName,
                    'description' => $description,
                    'status' => 'pending'
                ];

                // Limit to 10 requirements
                if (count($requirements) >= 10) {
                    break;
                }
            }
        }

        return $requirements;
    }

    /**
     * Extract descriptive text around a matched pattern
     *
     * @param string $context Full context text
     * @param string $match The matched text
     * @return string Description or snippet
     */
    private function extractContextDescription(string $context, string $match): string
    {
        // Find position of match
        $pos = strpos($context, $match);

        if ($pos === false) {
            return mb_strimwidth($match, 0, 80, '...');
        }

        // Get text before and after match
        $start = max(0, $pos - 50);
        $end = min(strlen($context), $pos + strlen($match) + 100);

        $snippet = substr($context, $start, $end - $start);
        $snippet = trim(preg_replace('/\s+/', ' ', $snippet));

        return mb_strimwidth($snippet, 0, 100, '...');
    }

    /**
     * Extract a clean requirement name from a sentence
     *
     * @param string $sentence
     * @return string
     */
    private function extractRequirementName(string $sentence): string
    {
        $patterns = [
            '/must\s+have\s+(.+?)(?:\.|,|;|$)/i',
            '/must\s+be\s+(.+?)(?:\.|,|;|$)/i',
            '/must\s+(.+?)(?:\.|,|;|$)/i',
            '/require(?:d|ment)s?:\s*(.+?)(?:\.|,|;|$)/i',
            '/have\s+(.+?)(?:\.|,|;|$)/i',
            '/hold\s+(.+?)(?:\.|,|;|$)/i',
            '/be\s+(.+?)(?:\.|,|;|$)/i',
            '/eligible\s+(?:to|for)\s+(.+?)(?:\.|,|;|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sentence, $matches)) {
                $name = trim($matches[1] ?? '');
                $name = preg_replace('/\s+/', ' ', $name);
                $words = explode(' ', $name);
                $name = implode(' ', array_slice($words, 0, 15));
                return mb_strimwidth($name, 0, 80, '...');
            }
        }

        return '';
    }
}

<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use App\Services\VisaRequirementService;

class Profile_Comparison extends WebController
{
    public function index()
    {
        if (empty($this->_current_member)) {
            header('Location: /account/login');
            exit;
        }

        return $this->pageView('profile_comparison');
    }

    /**
     * Extract user profile from chat logs
     */
    public function get_user_profile()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        try {
            $memberId = $this->_current_member['id'];

            // First try to get cached profile from database
            $cachedProfile = $this->getCachedProfile($memberId);

            // If cached profile exists and is recent (< 1 hour), use it
            if (!empty($cachedProfile) && $this->isCacheValid($cachedProfile)) {
                $this->pageResult([
                    'status' => 200,
                    'profile' => json_decode($cachedProfile->profile_data, true),
                    'cached' => true
                ]);
                return;
            }

            // Extract fresh profile from chat history using Gemini AI
            $chatHistory = $this->getChatHistory($memberId, 100);
            $profile = $this->extractProfileWithGemini($chatHistory);

            // Save profile to database
            if (!empty($profile)) {
                $this->saveProfile($memberId, $profile);
            }

            $this->pageResult([
                'status' => 200,
                'profile' => $profile,
                'cached' => false
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to extract user profile', [
                'error' => $e->getMessage()
            ]);

            $this->pageResult([
                'status' => 200,
                'profile' => []
            ]);
        }
    }

    /**
     * Get cached profile from database
     */
    protected function getCachedProfile($memberId)
    {
        try {
            return \DB::table('member')
                ->where('id', $memberId)
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if cached profile is still valid (less than 5 minutes old)
     * Reduced from 1 hour to ensure profile updates are reflected quickly
     */
    protected function isCacheValid($profile)
    {
        if (empty($profile->profile_updated_at)) {
            return false;
        }

        $lastUpdate = strtotime($profile->profile_updated_at);
        $fiveMinutesAgo = time() - 300; // 5 minutes instead of 1 hour (3600 seconds)

        return $lastUpdate > $fiveMinutesAgo;
    }

    /**
     * Save extracted profile to database
     */
    protected function saveProfile($memberId, $profile)
    {
        try {
            \DB::table('member')->where('id', $memberId)->update([
                'profile_data' => json_encode($profile),
                'profile_updated_at' => now(),
            ]);

            \Log::info("Profile saved for member {$memberId}", ['profile' => $profile]);
        } catch (\Exception $e) {
            \Log::warning("Failed to save profile for member {$memberId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get visa selection options for dropdowns
     */
    public function get_visa_options()
    {
        try {
            $this->pageResult([
                'status' => 200,
                'visa_options' => $this->getVisaSelectionOptions()
            ]);
        } catch (\Exception $e) {
            $this->pageResult([
                'status' => 500,
                'message' => 'Failed to load visa options'
            ]);
        }
    }

    /**
     * Debug endpoint - test RAG retrieval with Gemini cleaning
     */
    public function test_rag_retrieval()
    {
        try {
            $country = request('country', 'Australia');
            $visaType = request('visa_type', 'Temporary Graduate');

            $visaRequirementService = app(VisaRequirementService::class);

            // Test with 'policy' tag (same as chatbot)
            $result = $visaRequirementService->getVisaRequirements("{$visaType} {$country}", 'policy');

            $this->pageResult([
                'status' => 200,
                'country' => $country,
                'visa_type' => $visaType,
                'found' => $result['found'],
                'criteria_count' => count($result['criteria'] ?? []),
                'criteria' => $result['criteria'] ?? [],
                'source_matches' => $result['source_matches'] ?? 0
            ]);
        } catch (\Exception $e) {
            $this->pageResult([
                'status' => 500,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get visa requirements from RAG
     * Main endpoint for fetching requirements for selected country and visa type
     */
    public function get_ai_comparison()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        try {
            $country = request('country');
            $visaType = request('visa_type');

            // If no country/visa selected, return available options
            if (empty($country) || empty($visaType)) {
                $this->pageResult([
                    'status' => 200,
                    'visa_options' => $this->getVisaSelectionOptions(),
                    'requires_selection' => true
                ]);
                return;
            }

            // Fetch requirements from RAG for selected visa
            $visaRequirementService = app(VisaRequirementService::class);
            // Try different query formats to find matching data
            $queries = [
                "{$visaType} {$country}",
                "{$visaType}",
                "requirements for {$visaType} visa",
                "{$country} {$visaType} requirements",
                "What are the requirements for {$visaType}"
            ];

            $ragRequirements = null;
            try {
                foreach ($queries as $query) {
                    // Try with 'policy' tag first (same as chatbot uses)
                    $ragRequirements = $visaRequirementService->getVisaRequirements($query, 'policy');
                    if ($ragRequirements['found'] && !empty($ragRequirements['criteria'])) {
                        \Log::info("Found requirements for {$visaType} {$country} using query: {$query}", [
                            'criteria_count' => count($ragRequirements['criteria']),
                            'source_matches' => $ragRequirements['source_matches'] ?? 0
                        ]);
                        break; // Found data, use this query
                    }
                }

                if ($ragRequirements['found'] && !empty($ragRequirements['criteria'])) {
                    $requirements = array_map(function($criterion) {
                        return [
                            'requirement' => $criterion['name'] ?? '',
                            'description' => $criterion['description'] ?? ''
                        ];
                    }, $ragRequirements['criteria']);

                    $this->pageResult([
                        'status' => 200,
                        'country' => $country,
                        'visa_type' => $visaType,
                        'requirements' => $requirements,
                        'found' => true,
                        'count' => count($requirements)
                    ]);
                } else {
                    \Log::warning("No requirements found for {$visaType} {$country}");

                    $this->pageResult([
                        'status' => 404,
                        'country' => $country,
                        'visa_type' => $visaType,
                        'requirements' => [],
                        'found' => false,
                        'message' => 'No requirements found for this visa type in RAG',
                        'visa_options' => $this->getVisaSelectionOptions()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning("Failed to fetch RAG criteria for {$visaType} {$country}", [
                    'error' => $e->getMessage()
                ]);

                $this->pageResult([
                    'status' => 500,
                    'message' => 'Failed to fetch requirements from RAG',
                    'visa_options' => $this->getVisaSelectionOptions()
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Get AI comparison failed', [
                'error' => $e->getMessage(),
                'member_id' => $this->_current_member['id']
            ]);

            $this->pageResult([
                'status' => 500,
                'message' => 'Failed to process request'
            ]);
        }
    }


    protected function getChatHistory($memberId, $limit = 20)
    {
        return \DB::table('chat_log')
            ->where('member_id', $memberId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse() // Chronological order
            ->toArray();
    }

    /**
     * Get visa selection options for dropdowns
     * Returns available countries and visa types
     */
    protected function getVisaSelectionOptions()
    {
        return [
            'countries' => [
                'Australia',
                'Canada',
                'United Kingdom',
                'United States',
                'New Zealand',
                'Germany',
                'Singapore'
            ],
            'visa_types_by_country' => [
                'Australia' => [
                    'Skilled Independent' => '189',
                    'Skilled Nominated' => '190',
                    'Temporary Graduate' => '485',
                    'Skilled Independent (Regional)' => '191',
                    'Employer Sponsored' => '482',
                    'Skilled Migration' => '189/190/491'
                ],
                'Canada' => [
                    'Express Entry' => 'EE',
                    'Provincial Nominee Program' => 'PNP',
                    'Canadian Experience Class' => 'CEC',
                    'Federal Skilled Worker' => 'FSW'
                ],
                'United Kingdom' => [
                    'Skilled Worker Visa' => 'SWV',
                    'Intra-Company Transfer' => 'ICT',
                    'Graduate Route' => 'GR',
                    'Points-Based System' => 'PBS'
                ],
                'United States' => [
                    'H-1B' => 'H1B',
                    'EB-3 Skilled Worker' => 'EB3',
                    'EB-2 Professional' => 'EB2',
                    'O-1 Extraordinary Ability' => 'O1'
                ],
                'New Zealand' => [
                    'Skilled Migrant' => 'SM',
                    'Essential Skills' => 'ES',
                    'Work to Residence' => 'WR',
                    'Post-Study Work Visa' => 'PSWV'
                ],
                'Germany' => [
                    'EU Blue Card' => 'EBC',
                    'Skilled Worker Visa' => 'SWV',
                    'Job Seeker Visa' => 'JSV',
                    'Freelancer Visa' => 'FV'
                ],
                'Singapore' => [
                    'Tech.Pass' => 'TP',
                    'EntrePass' => 'EP',
                    'Work Permit' => 'WP',
                    'Employment Pass' => 'EP'
                ]
            ]
        ];
    }

    /**
     * Extract user profile using Gemini AI for intelligent understanding
     * More accurate than regex patterns for distinguishing real info from context
     */
    protected function extractProfileWithGemini($chatHistory): array
    {
        try {
            // Extract only user messages
            $userMessages = [];
            foreach ($chatHistory as $message) {
                if ($message->type === 'ask') {
                    $userMessages[] = $message->content;
                }
            }

            if (empty($userMessages)) {
                return [];
            }

            $chatText = implode("\n", $userMessages);

            // Truncate to avoid token limits
            $chatText = mb_strimwidth($chatText, 0, 4000, '...');

            $prompt = <<<PROMPT
You are a migration assistant helping understand user profiles. Extract user personal information from this chat conversation.

CRITICAL INSTRUCTION: Only extract information that the USER explicitly stated about THEMSELVES.
NEVER extract information about visa requirements, eligibility criteria, or system-generated recommendations.
Example: If text contains "must be 45 years old" - this is a REQUIREMENT, not user profile data. Ignore it.

CHAT HISTORY:
$chatText

TASK: Extract ONLY information directly stated by the user about themselves AND their migration interests.
- Ignore all visa requirements, visa criteria, and eligibility information
- Ignore all system messages or bot recommendations
- Only extract personal facts the user disclosed about themselves
- Extract the country and visa type they mentioned being interested in

OUTPUT FORMAT - Return ONLY valid JSON:
{
  "age": "number (18-100) or null",
  "education": "degree field or null (e.g., 'Computer Science', 'Bachelor of Commerce')",
  "occupation": "job title or null (e.g., 'Software Engineer', 'Data Analyst')",
  "experience": "years as number or null (e.g., 5, 10)",
  "english_level": "fluency level or null (e.g., 'Advanced', 'Intermediate', 'Fluent')",
  "ielts_score": "IELTS X.X format or null",
  "toefl_score": "TOEFL XXX format or null",
  "pte_score": "PTE XX format or null",
  "nationality": "country name or null",
  "interested_country": "country name or null (e.g., 'Australia', 'Canada')",
  "interested_visa_type": "visa type name or null (e.g., 'Temporary Graduate', 'Skilled Nominated', 'Work Visa')"
}

Rules:
- Age: Extract ONLY if user explicitly states their age (e.g., "I am 28", "I'm 32 years old"). NEVER extract age from requirement text like "between 18 to 45 years".
- Education: Extract degree/field ONLY if explicitly mentioned (e.g., "Bachelor of Science")
- Occupation: Extract job title ONLY (not location like "Gold Coast")
- Experience: Extract years ONLY if explicitly mentioned by user (e.g., "I have 5 years experience")
- English level: Extract ONLY if user states fluency level about themselves
- Nationality: Extract ONLY if user explicitly states where they are from
- Interested Country: Extract if user mentions wanting to migrate to a specific country (e.g., "I want to move to Australia", "migrate to Canada")
- Interested Visa Type: Extract if user mentions a specific visa type (e.g., "485 visa" → "Temporary Graduate", "190 visa" → "Skilled Nominated", "skilled migration")
- Return null for any field not explicitly stated by the user
- NO markdown, NO explanations, ONLY valid JSON
PROMPT;

            $apiKey = env('GEMINI_API_KEY');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $apiKey;

            $data = [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 1024]
            ];

            $response = \Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $data);

            if (!$response->successful()) {
                \Log::warning('Gemini profile extraction failed', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return [];
            }

            $result = $response->json();

            // Extract text from Gemini response
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $text = $result['candidates'][0]['content']['parts'][0]['text'];

                // Handle markdown code fences (```json ... ```)
                if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $matches)) {
                    $text = $matches[1];
                }

                // Parse JSON response
                $profile = json_decode(trim($text), true);

                if (is_array($profile)) {
                    // Clean up the profile - remove null values and ensure correct types
                    $cleaned = [];

                    if (!empty($profile['age']) && is_numeric($profile['age'])) {
                        $age = intval($profile['age']);
                        if ($age >= 18 && $age <= 100) {
                            $cleaned['age'] = $age;
                        }
                    }

                    if (!empty($profile['education'])) {
                        $cleaned['education'] = trim(strval($profile['education']));
                    }

                    if (!empty($profile['occupation'])) {
                        $cleaned['occupation'] = trim(strval($profile['occupation']));
                    }

                    if (!empty($profile['experience']) && is_numeric($profile['experience'])) {
                        $years = intval($profile['experience']);
                        if ($years >= 0 && $years <= 60) {
                            $cleaned['experience'] = $years . ' years';
                        }
                    }

                    if (!empty($profile['english_level'])) {
                        $cleaned['english_level'] = trim(strval($profile['english_level']));
                    }

                    if (!empty($profile['ielts_score'])) {
                        $cleaned['ielts_score'] = trim(strval($profile['ielts_score']));
                    }

                    if (!empty($profile['toefl_score'])) {
                        $cleaned['toefl_score'] = trim(strval($profile['toefl_score']));
                    }

                    if (!empty($profile['pte_score'])) {
                        $cleaned['pte_score'] = trim(strval($profile['pte_score']));
                    }

                    if (!empty($profile['nationality'])) {
                        $cleaned['nationality'] = trim(strval($profile['nationality']));
                    }

                    if (!empty($profile['interested_country'])) {
                        $cleaned['interested_country'] = trim(strval($profile['interested_country']));
                    }

                    if (!empty($profile['interested_visa_type'])) {
                        $cleaned['interested_visa_type'] = trim(strval($profile['interested_visa_type']));
                    }

                    return $cleaned;
                }
            }

            return [];
        } catch (\Exception $e) {
            \Log::warning('Gemini profile extraction exception', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

}

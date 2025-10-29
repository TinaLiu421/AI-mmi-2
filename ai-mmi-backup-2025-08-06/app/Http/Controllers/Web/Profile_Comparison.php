<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;

class Profile_Comparison extends WebController
{
    public function index()
    {
        if (empty($this->_current_member)) {
            $this->doRedirect($this->toURL('account_login'));
            return;
        }

        return $this->pageView('profile_comparison');
    }

    public function get_ai_comparison()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        $chatHistory = $this->getChatHistory($this->_current_member['id'], 40);

        if (empty($chatHistory)) {
            $this->pageResult([
                'status' => 400,
                'message' => 'No chat history found. Please chat with AI first to share your information.'
            ]);
            return;
        }

        $prompt = $this->buildGeminiComparisonPromptFromChat($chatHistory);

        try {
            $geminiResponse = $this->callGeminiForComparison($prompt);

            $this->pageResult([
                'status' => 200,
                'comparison' => $geminiResponse
            ]);

        } catch (\Exception $e) {
            \Log::error('Gemini comparison failed', [
                'error' => $e->getMessage(),
                'member_id' => $this->_current_member['id']
            ]);

            $this->pageResult([
                'status' => 500,
                'message' => 'Failed to generate comparison. Please try again.'
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

    protected function buildGeminiComparisonPromptFromChat($chatHistory)
    {
        // Format chat history
        $conversationText = "";
        foreach ($chatHistory as $message) {
            $role = $message->type === 'ask' ? 'User' : 'AI';
            $conversationText .= "{$role}: {$message->content}\n\n";
        }

        return <<<PROMPT
You are a visa eligibility expert. Based on our previous conversation below, analyze the user's eligibility for relevant visa options.

**Previous Conversation:**
{$conversationText}

**Your Task:**
1. Read the conversation and identify ALL relevant user information (age, occupation, education, English level, work experience, country preference, etc.)
2. Use the MOST RECENT information if user corrected/updated anything during chat
3. Identify the TOP 3 most relevant visa options for this profile (based on their target country or best matches)
4. For EACH visa, provide a detailed comparison in this EXACT JSON format:

```json
{
  "visa_options": [
    {
      "country": "Australia",
      "visa_name": "Skilled Independent (189)",
      "visa_code": "189",
      "description": "Points-based permanent residence for skilled workers",
      "match_score": 85,
      "requirements": [
        {
          "requirement": "Age",
          "visa_requirement": "Under 45",
          "user_value": "28",
          "status": "met",
          "is_critical": true,
          "details": "Meets requirement"
        },
        {
          "requirement": "English Proficiency",
          "visa_requirement": "IELTS 6.0+",
          "user_value": "Not provided",
          "status": "missing",
          "is_critical": true,
          "details": "English test needed"
        }
      ],
      "critical_met": 3,
      "critical_total": 4,
      "recommendation": {
        "level": "success",
        "message": "Strong match! You meet most critical requirements.",
        "icon": "thumbs-up",
        "next_steps": [
          "Take IELTS test (target 7.0+)",
          "Get skills assessment for your occupation",
          "Calculate your points score"
        ]
      }
    }
  ]
}
```

**CRITICAL - Match Score Calculation (STRICTLY FOLLOW):**

**FORMULA (NO EXCEPTIONS):**
```
match_score = (critical_met / critical_total) × 70 + (non_critical_met / non_critical_total) × 30
```

**RULES:**
1. **ONLY "met" status counts as positive** → Value = 1
2. **"not_met" and "missing" both count as ZERO** → Value = 0
3. **Round result to nearest integer**

**EXAMPLES (Follow exactly):**

Example 1:
- 6 critical: 0 met, 4 not_met, 2 missing
  → critical_met = 0, critical_total = 6
- 2 non-critical: 0 met, 2 missing
  → non_critical_met = 0, non_critical_total = 2
- **match_score = (0/6) × 70 + (0/2) × 30 = 0**

Example 2:
- 4 critical: 2 met, 1 not_met, 1 missing
  → critical_met = 2, critical_total = 4
- 3 non-critical: 2 met, 1 missing
  → non_critical_met = 2, non_critical_total = 3
- **match_score = (2/4) × 70 + (2/3) × 30 = 35 + 20 = 55**

Example 3:
- 5 critical: 1 met, 4 not_met
  → critical_met = 1, critical_total = 5
- 0 non-critical
  → non_critical_met = 0, non_critical_total = 0
- **match_score = (1/5) × 70 + 0 = 14**

**VALIDATION:**
- If ALL requirements are "not_met" or "missing" → match_score MUST = 0
- If ZERO critical requirements met → match_score CANNOT exceed 30
- match_score range: 0-100

**Recommendation message:**
- If match_score < 30: "⚠️ Low eligibility. Consider other visa options or address critical gaps."
- If many "missing": Add "⚠️ Score may improve once you provide: [list missing items]"

**Important:**
- Use your latest knowledge of REAL visa requirements from official government sources
- Status options:
  * "met" = User clearly meets requirement
  * "not_met" = User clearly does NOT meet requirement
  * "missing" = Information not provided (treat as not met for scoring)
- **Keep all text CONCISE and SHORT:**
  * visa_requirement: Brief (e.g. "IELTS 6.0+" not "IELTS 6.0 or equivalent")
  * user_value: Brief (e.g. "28" not "28 years old")
  * details: One short sentence max (e.g. "Meets requirement" or "Test needed")
  * recommendation message: 1-2 short sentences max
  * next_steps: Short action items (e.g. "Take IELTS" not "You should consider taking...")
- Return ONLY valid JSON, no markdown formatting
PROMPT;
    }
    protected function callGeminiForComparison($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=' . $apiKey;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3, // Lower temperature for more consistent output
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 4096,
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Gemini API returned error: ' . $response);
        }

        $result = json_decode($response, true);
        $geminiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Extract JSON from Gemini response
        $jsonMatch = [];
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $geminiText, $jsonMatch)) {
            $geminiText = $jsonMatch[1];
        }

        $comparisonData = json_decode($geminiText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON from Gemini: ' . json_last_error_msg());
        }

        // Validate and recalculate match scores to ensure accuracy
        $comparisonData = $this->validateAndRecalculateScores($comparisonData);

        return $comparisonData;
    }

    protected function validateAndRecalculateScores($comparisonData)
    {
        if (!isset($comparisonData['visa_options']) || !is_array($comparisonData['visa_options'])) {
            return $comparisonData;
        }

        foreach ($comparisonData['visa_options'] as &$visa) {
            if (!isset($visa['requirements']) || !is_array($visa['requirements'])) {
                continue;
            }

            $criticalMet = 0;
            $criticalTotal = 0;
            $nonCriticalMet = 0;
            $nonCriticalTotal = 0;

            // Count met requirements
            foreach ($visa['requirements'] as $req) {
                $isCritical = $req['is_critical'] ?? false;
                $status = $req['status'] ?? 'missing';

                if ($isCritical) {
                    $criticalTotal++;
                    if ($status === 'met') {
                        $criticalMet++;
                    }
                } else {
                    $nonCriticalTotal++;
                    if ($status === 'met') {
                        $nonCriticalMet++;
                    }
                }
            }

            // Calculate correct match score
            $criticalScore = $criticalTotal > 0 ? ($criticalMet / $criticalTotal) * 70 : 0;
            $nonCriticalScore = $nonCriticalTotal > 0 ? ($nonCriticalMet / $nonCriticalTotal) * 30 : 0;
            $correctMatchScore = round($criticalScore + $nonCriticalScore);

            // Log if Gemini's score was wrong
            if (isset($visa['match_score']) && $visa['match_score'] != $correctMatchScore) {
                \Log::warning('Match score corrected', [
                    'visa' => $visa['visa_name'] ?? 'Unknown',
                    'gemini_score' => $visa['match_score'],
                    'correct_score' => $correctMatchScore,
                    'critical_met' => $criticalMet,
                    'critical_total' => $criticalTotal,
                    'non_critical_met' => $nonCriticalMet,
                    'non_critical_total' => $nonCriticalTotal
                ]);
            }

            // Override with correct score
            $visa['match_score'] = $correctMatchScore;
            $visa['critical_met'] = $criticalMet;
            $visa['critical_total'] = $criticalTotal;

            // Update recommendation level based on corrected score
            if ($correctMatchScore >= 70) {
                $visa['recommendation']['level'] = 'success';
            } elseif ($correctMatchScore >= 50) {
                $visa['recommendation']['level'] = 'warning';
            } else {
                $visa['recommendation']['level'] = 'info';
            }

            // Add low eligibility warning if score is below 30
            if ($correctMatchScore < 30 && isset($visa['recommendation']['message'])) {
                if (strpos($visa['recommendation']['message'], 'Low eligibility') === false) {
                    $visa['recommendation']['message'] = '⚠️ Low eligibility. ' . $visa['recommendation']['message'];
                }
            }
        }

        return $comparisonData;
    }

}

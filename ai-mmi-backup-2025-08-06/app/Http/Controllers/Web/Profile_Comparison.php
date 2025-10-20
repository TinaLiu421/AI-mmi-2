<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;

class Profile_Comparison extends WebController
{
    /**
     * Show profile comparison page (main index)
     */
    public function index()
    {
        if (empty($this->_current_member)) {
            header('Location: /account/login');
            exit;
        }

        // Simple page view - AI comparison uses chat history directly
        return $this->pageView('profile_comparison');
    }


    /**
     * AI-Powered Comparison: Let Gemini analyze profile and generate comparison
     * Uses DIRECT chat history instead of extracted data for 100% accuracy
     */
    public function get_ai_comparison()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        // Get recent chat history (last 20 messages for context)
        $chatHistory = $this->getChatHistory($this->_current_member['id'], 20);

        if (empty($chatHistory)) {
            $this->pageResult([
                'status' => 400,
                'message' => 'No chat history found. Please chat with AI first to share your information.'
            ]);
            return;
        }

        // Build a prompt for Gemini using ACTUAL chat history
        $prompt = $this->buildGeminiComparisonPromptFromChat($chatHistory);

        // Call Gemini API
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

    /**
     * Get chat history for context
     */
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
     * Build prompt for Gemini using actual chat history (no extraction needed!)
     */
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

**CRITICAL - Match Score Calculation:**
1. **"missing" information = NOT MET** when calculating match_score
2. Match score formula:
   - Critical requirements weight: 70%
   - Non-critical requirements weight: 30%
   - Only "met" status counts as positive
   - "missing" and "not_met" both count as 0

3. Example calculation:
   - 4 critical requirements: 2 met, 1 not_met, 1 missing
     → Critical score = (2/4) × 70 = 35%
   - 3 non-critical: 2 met, 1 missing
     → Non-critical score = (2/3) × 30 = 20%
   - **Total match_score = 55%**

4. In recommendation message, CLEARLY state:
   - "⚠️ Score may be higher once you provide missing information"
   - List what's missing and its impact

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

    /**
     * Call Gemini API to generate comparison
     */
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

        return $comparisonData;
    }

}

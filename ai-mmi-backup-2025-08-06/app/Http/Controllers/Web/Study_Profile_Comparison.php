<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;

class Study_Profile_Comparison extends WebController
{
    public function index()
    {
        if (empty($this->_current_member)) {
            $this->doRedirect($this->toURL('account_login'));
            return;
        }

        // Check if user's latest assessment needs AI analysis
        $latestAssessment = DB::table('study_eligibility_assessments')
            ->where('member_id', $this->_current_member['id'])
            ->orderBy('created_at', 'desc')
            ->first();

        \Log::info('Study Profile Comparison - Latest Assessment:', [
            'member_id' => $this->_current_member['id'],
            'has_assessment' => !empty($latestAssessment),
            'ai_assessment' => $latestAssessment ? $latestAssessment->ai_assessment : 'N/A',
            'ai_is_empty' => $latestAssessment ? empty($latestAssessment->ai_assessment) : 'N/A'
        ]);

        $autoGenerate = $latestAssessment && empty($latestAssessment->ai_assessment);

        \Log::info('Setting auto_generate to:', ['auto_generate' => $autoGenerate]);

        $this->_page_data['auto_generate'] = $autoGenerate;
        
        return $this->pageView('study_profile_comparison');
    }

    public function get_ai_comparison()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        // Get the latest study eligibility assessment from database
        $assessment = DB::table('study_eligibility_assessments')
            ->where('member_id', $this->_current_member['id'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (empty($assessment)) {
            $this->pageResult([
                'status' => 400,
                'message' => 'No study eligibility assessment found. Please complete the eligibility check first.'
            ]);
            return;
        }

        $prompt = $this->buildComparisonPrompt($assessment);

        try {
            $geminiResponse = $this->callGeminiForComparison($prompt);

            // Update the AI assessment in the database
            DB::table('study_eligibility_assessments')
                ->where('id', $assessment->id)
                ->update([
                    'ai_assessment' => $geminiResponse,
                    'updated_at' => now()
                ]);

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

    protected function buildComparisonPrompt($assessment)
    {
        $countries = json_decode($assessment->countries, true) ?? [];
        $testResults = json_decode($assessment->test_results, true) ?? [];

        $prompt = "Based on the following study abroad profile, provide a detailed eligibility assessment:\n\n";
        
        $prompt .= "**Student Profile:**\n";
        $prompt .= "- Interested Countries: " . implode(', ', $countries) . "\n";
        $prompt .= "- Nationality: " . ($assessment->nationality ?? 'Not specified') . "\n";
        $prompt .= "- Current Residency: " . ($assessment->residency ?? 'Not specified') . "\n";
        $prompt .= "- Age: " . ($assessment->age ?? 'Not specified') . "\n";
        $prompt .= "- Education Level: " . ($assessment->education_level ?? 'Not specified') . "\n";
        $prompt .= "- English Test Completed: " . ($assessment->english_test_completed ?? 'Not specified') . "\n";
        
        if (!empty($testResults)) {
            $prompt .= "- English Test Results:\n";
            foreach ($testResults as $test => $score) {
                $prompt .= "  * " . str_replace('_', ' ', $test) . ": " . $score . "\n";
            }
        }
        
        $prompt .= "- Intended Study Level: " . ($assessment->study_level ?? 'Not specified') . "\n";
        $prompt .= "- Field of Study: " . ($assessment->field_of_study ?? 'Not specified') . "\n";
        $prompt .= "- Budget: " . ($assessment->budget ?? 'Not specified') . "\n\n";

        $prompt .= "**Please provide a comprehensive analysis in the following JSON format:**\n\n";
        $prompt .= <<<'JSON'
```json
{
  "country_comparisons": [
    {
      "country": "Country Name",
      "overall_eligibility": "High/Medium/Low",
      "eligibility_score": 85,
      "requirements": [
        {
          "requirement_name": "Age Requirement",
          "required_value": "18-35 years",
          "user_value": "25 years",
          "status": "✓ Met" or "✗ Not Met" or "⚠ Partially Met",
          "notes": "Additional context or advice"
        }
      ],
      "strengths": ["List of strengths"],
      "challenges": ["List of challenges"],
      "recommendations": ["List of recommendations"],
      "estimated_timeline": "6-12 months",
      "estimated_cost": "$20,000-$30,000 USD per year"
    }
  ],
  "overall_recommendation": "Detailed recommendation text",
  "next_steps": ["Step 1", "Step 2", "Step 3"]
}
```
JSON;

        return $prompt;
    }

    protected function callGeminiForComparison($prompt)
    {
        $apiKey = config('services.xai.api_key');
        
        if (empty($apiKey)) {
            throw new \Exception('XAI API key not configured');
        }

        $url = 'https://api.x.ai/v1/chat/completions';

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'model' => 'grok-3',
            'temperature' => 0.7
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification if needed
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            \Log::error('CURL error', ['error' => $curlError]);
            throw new \Exception('CURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            \Log::error('XAI API error', ['code' => $httpCode, 'response' => $response]);
            throw new \Exception('XAI API returned error: ' . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        throw new \Exception('Unexpected XAI API response format');
    }
}

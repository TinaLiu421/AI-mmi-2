<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;

class Migration_Profile_Comparison extends WebController
{
    public function index()
    {
        if (empty($this->_current_member)) {
            $this->doRedirect($this->toURL('account_login'));
            return;
        }

        // Check if user's latest assessment needs AI analysis
        $latestAssessment = DB::table('migration_eligibility_assessments')
            ->where('member_id', $this->_current_member['id'])
            ->orderBy('created_at', 'desc')
            ->first();

        $autoGenerate = $latestAssessment && empty($latestAssessment->ai_assessment);

        $this->_page_data['auto_generate'] = $autoGenerate;
        
        return $this->pageView('migration_profile_comparison');
    }

    public function get_ai_comparison()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        // Get the latest migration eligibility assessment from database
        $assessment = DB::table('migration_eligibility_assessments')
            ->where('member_id', $this->_current_member['id'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (empty($assessment)) {
            $this->pageResult([
                'status' => 400,
                'message' => 'No migration eligibility assessment found. Please complete the eligibility check first.'
            ]);
            return;
        }

        $prompt = $this->buildComparisonPrompt($assessment);

        try {
            $geminiResponse = $this->callGeminiForComparison($prompt);

            // Update the AI assessment in the database
            DB::table('migration_eligibility_assessments')
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
        $visaTypes = json_decode($assessment->visa_types, true) ?? [];
        $testResults = json_decode($assessment->test_results, true) ?? [];

        $prompt = "Based on the following migration profile, provide a detailed eligibility assessment:\n\n";
        
        $prompt .= "**Applicant Profile:**\n";
        $prompt .= "- Preferred Countries: " . implode(', ', $countries) . "\n";
        $prompt .= "- Interested Visa Types: " . implode(', ', $visaTypes) . "\n";
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
        
        $prompt .= "- Occupation: " . ($assessment->occupation ?? 'Not specified') . "\n";
        $prompt .= "- Total Work Experience: " . ($assessment->total_work_experience ?? 'Not specified') . " years\n";
        $prompt .= "- Work Experience in Current Occupation: " . ($assessment->occupation_work_experience ?? 'Not specified') . " years\n";
        $prompt .= "- Work Experience in Destination Country: " . ($assessment->destination_work_experience ?? 'Not specified') . "\n";
        
        if ($assessment->destination_work_experience === 'Yes') {
            $prompt .= "- Years of Destination Country Experience: " . ($assessment->destination_work_years ?? 'Not specified') . " years\n";
        }
        
        $prompt .= "- Job Offer from Destination Country: " . ($assessment->job_offer ?? 'Not specified') . "\n";
        $prompt .= "- Outstanding Professional Achievements: " . ($assessment->outstanding_achievements ?? 'Not specified') . "\n";
        
        if ($assessment->outstanding_achievements === 'Yes') {
            $prompt .= "- Achievement Details: " . ($assessment->achievements_details ?? 'Not specified') . "\n";
        }
        
        if (!empty($assessment->cv_file_path)) {
            $prompt .= "- CV/Resume: Uploaded\n";
        }
        
        $prompt .= "\n**Please provide a comprehensive analysis in the following JSON format:**\n\n";
        $prompt .= <<<'JSON'
```json
{
  "visa_options": [
    {
      "country": "Country Name",
      "visa_type": "Visa Type Name",
      "overall_eligibility": "High/Medium/Low",
      "eligibility_score": 85,
      "points_breakdown": {
        "age": {"points": 30, "max": 30, "notes": ""},
        "education": {"points": 25, "max": 25, "notes": ""},
        "work_experience": {"points": 15, "max": 25, "notes": ""},
        "english_proficiency": {"points": 20, "max": 30, "notes": ""}
      },
      "total_points": 90,
      "minimum_points_required": 67,
      "requirements": [
        {
          "requirement_name": "Age Requirement",
          "required_value": "18-44 years",
          "user_value": "35 years",
          "status": "✓ Met" or "✗ Not Met" or "⚠ Partially Met",
          "notes": "Additional context or advice"
        }
      ],
      "strengths": ["List of strengths"],
      "challenges": ["List of challenges"],
      "recommendations": ["List of recommendations"],
      "estimated_processing_time": "12-18 months",
      "estimated_cost": "$5,000-$8,000 USD"
    }
  ],
  "overall_recommendation": "Detailed recommendation text",
  "priority_actions": ["Action 1", "Action 2", "Action 3"],
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

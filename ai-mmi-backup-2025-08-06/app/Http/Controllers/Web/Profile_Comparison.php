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
        // Format chat history - profile edits are already in chat_log as user messages
        $conversationText = "";
        foreach ($chatHistory as $message) {
            $role = $message->type === 'ask' ? 'User' : 'AI';
            $conversationText .= "{$role}: {$message->content}\n\n";
        }

        \Log::info('Built conversation text from chat history:', ['messageCount' => count($chatHistory)]);

        return <<<PROMPT
You are a visa eligibility expert with deep knowledge of official visa requirements. Based on our previous conversation below, analyze the user's eligibility for relevant visa options.

**Previous Conversation:**
{$conversationText}

**🔴 IMPORTANT: If the user mentioned "I just updated my profile information" in the conversation, use THOSE updated values in your analysis, not old information from earlier in the chat.**

**Your Task:**
1. Read the conversation and identify ALL relevant user information (age, occupation, education, English level, work experience, country preference, etc.)
2. **If user recently updated information (look for "I just updated my profile information"), use the NEW values they provided**
3. For requirements with updated values, update the "user_value" field and status in your JSON output
3. For any requirement NOT in the updated section, use the chat history
4. Identify the TOP 3 most relevant visa options for this profile (based on their target country or best matches)
5. For EACH visa, provide a detailed comparison in this EXACT JSON format:

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
          "visa_requirement": "Under 45 years old",
          "user_value": "28",
          "status": "met",
          "is_critical": true,
          "details": "Meets requirement"
        },
        {
          "requirement": "English Proficiency",
          "visa_requirement": "IELTS 6.0 (min 6.0 in each band) OR TOEFL 60+ OR PTE 50+ OR Cambridge CAE Grade C",
          "user_value": "Not provided",
          "status": "missing",
          "is_critical": true,
          "details": "Must meet one test requirement. IELTS requires minimum 6.0 in all bands (Listening, Reading, Writing, Speaking)"
        },
        {
          "requirement": "Skills Assessment",
          "visa_requirement": "Positive skills assessment from ACS (Australian Computer Society) for nominated IT occupation",
          "user_value": "Assessed by ACS",
          "status": "met",
          "is_critical": true,
          "details": "Skills assessment completed and approved"
        },
        {
          "requirement": "Work Experience",
          "visa_requirement": "Minimum 3 years in nominated occupation",
          "user_value": "5 years as Software Engineer",
          "status": "met",
          "is_critical": true,
          "details": "Exceeds minimum requirement"
        }
      ],
      "critical_met": 2,
      "critical_total": 3,
      "recommendation": {
        "level": "success",
        "message": "Strong match! You meet most critical requirements.",
        "icon": "thumbs-up",
        "next_steps": [
          "Take IELTS test (target 7.0+)",
          "Get skills assessment from VETASSESS or AHPRA",
          "Calculate points for visa application"
        ]
      }
    }
  ]
}
```

**CRITICAL REQUIREMENTS GUIDELINES:**

⚠️ **CRITICAL RULE: EVERY requirement must be SPECIFIC and ACTIONABLE. NEVER use generic phrases like "meet X requirements" - ALWAYS specify WHAT exactly needs to be done or provided.**

**ENGLISH PROFICIENCY - Use Specific Test Scores with Band/Section Details:**

**IELTS (Band Score - all bands must meet minimum):**
- 6.0: Listening 6.0, Reading 6.0, Writing 6.0, Speaking 6.0
- 6.5: Listening 6.5, Reading 6.5, Writing 6.5, Speaking 6.5
- 7.0: Listening 7.0, Reading 7.0, Writing 7.0, Speaking 7.0
- 7.5: Listening 7.5, Reading 7.5, Writing 7.5, Speaking 7.5
- 8.0+: Listening 8.0+, Reading 8.0+, Writing 8.0+, Speaking 8.0+
- Example: "IELTS 6.0 (min 6.0 in each band)"

**TOEFL iBT (Score out of 120 - total score):**
- 60: Basic proficiency (approx CEFR B1)
- 79: Intermediate (approx CEFR B2)
- 93: Upper-intermediate (approx CEFR C1)
- 102: Advanced (approx CEFR C1+)
- 120: Mastery
- Example: "TOEFL 79+ (reading, writing, listening, speaking combined)"

**PTE Academic (Score out of 90 - each section scored separately):**
- 50: Communicative (approx CEFR B1)
- 58: Competent (approx CEFR B2)
- 65: Highly Proficient (approx CEFR C1)
- 79: Expert (approx CEFR C1+)
- 90: Mastery
- Example: "PTE 58+ (communicative in all areas)"

**Cambridge CAE/CPE (Grade):**
- CAE (Grade A-E): A (193-230/200), B (180-192/200), C (160-179/200)
- CPE (Grade A-E): A (200-230/200), B (184-199/200), C (160-183/200)
- Example: "Cambridge CAE Grade B or CPE Grade C or higher"

**CELPIP (for Canada - Score out of 12 per skill):**
- 7: Proficient (approx CEFR B2)
- 8: Proficient (high, approx CEFR C1)
- 9: Proficient (very high, approx CEFR C1+)
- Example: "CELPIP CLB 7+ (minimum 7 in all skills)"

**CLB/NCLC (for Canada - Level 1-12):**
- CLB 4: Basic (approx CEFR A2)
- CLB 5: Basic (higher, approx CEFR B1)
- CLB 7: Intermediate (approx CEFR B2)
- CLB 8: Intermediate (higher, approx CEFR B2/C1)
- CLB 10: Advanced (approx CEFR C1)
- Example: "CLB 7+ (Canadian Language Benchmark)"

**When multiple tests are accepted, include all options:**
- Example: "IELTS 6.0 (min 6.0 in each band) OR TOEFL 60+ OR PTE 50+ OR Cambridge CAE Grade C"
- Clearly show which test is accepted and its exact score/band requirement
- Include any per-band/per-section minimum if applicable

**DO NOT USE:**
- "Proficient English" ❌
- "Good English" ❌
- "Fluent English" ❌
- "Working knowledge" ❌

**AGE REQUIREMENT - Use Exact Ranges:**
- "Under 45" (NOT "working age")
- "Between 25-55" (NOT "prime working age")
- "Under 35" (if applicable)

**SALARY/INCOME - Use Specific Amounts:**
- "USD $50,000+ per year"
- "CAD $55,000+ annually"
- "£30,000+ per annum"
- (NOT "competitive salary")

**WORK EXPERIENCE & SKILLS ASSESSMENT - Use Specific Durations and Standards:**
- "Minimum 2 years in nominated occupation" (NOT "some experience")
- "Minimum 5 years in nominated field" (NOT "extensive experience")
- "Minimum 3 years as Software Engineer" (be specific about role)
- "Full-time work only (part-time excluded)" (if applicable)
- "Recent experience (within last 5 years)" (if required)
- Specify: "in nominated occupation" OR "in related field" OR "in any skilled occupation"
- Example: "Minimum 3 years full-time skilled work experience in nominated occupation"

**SKILLS ASSESSMENT - Use Specific Standards:**
- "Skills assessment from [specific body]" - Specify authority:
  - Australia: "ACS (Australian Computer Society) for IT roles", "Engineers Australia for engineering", "VETASSESS for other skilled occupations"
  - Canada: "Provincial regulatory bodies or licensing boards"
  - UK: "UKVI-approved assessment body specific to occupation"
  - Specify: "Positive assessment" OR "Recognized qualification equivalent" (NOT "positive skills assessment")
- Example: "Positive skills assessment from ACS (Australian Computer Society) for nominated IT occupation"
- Example: "Professional license/certification from Engineers Australia or equivalent"

**EMPLOYER SPONSORSHIP - Use Specific Requirements:**
- "Sponsorship from approved employer in [country/region]" (specify country/region requirement)
- For Australia Regional: "Sponsorship from employer in designated regional area (outside major cities)"
- For Australia General: "Sponsorship from employer on Approved Sponsor List (ASL)"
- For Canada: "Confirmed job offer from Canadian employer (Labour Market Impact Assessment may apply)"
- For UK: "UK Skilled Worker visa sponsorship from licensed sponsor employer"
- "No requirement" OR "Employer sponsorship not required" (if optional)
- Example: "Sponsorship from approved employer in designated regional area (Australia)"
- Example: "Confirmed job offer from Canadian employer in occupation on NOC list"

**EDUCATION & QUALIFICATIONS - Use Specific Levels, Fields, and Sources:**
- "Bachelor's degree or higher" (NOT "good education")
- "Minimum 12 years of schooling (high school graduation)" (NOT "adequate education")
- "Diploma from accredited institution" (NOT "post-secondary education")
- Specify field if required: "Bachelor's degree in Engineering discipline"
- Specify institution requirements: "From CRICOS-registered Australian university" (if applicable)
- "Recognized qualification equivalent to [country system]" (specify equivalency)
- "Must be from [country] institution" (if location-specific)
- "Recent qualification (obtained within last 5 years)" (if recency required)
- "Professional certification/license required" (if applicable)
- Example: "Bachelor's degree in any discipline from recognized institution"
- Example: "Masters degree from CRICOS-registered Australian university (obtained within last 5 years)"

**When user qualification doesn't meet requirement, explain WHY:**
- If "not met": Specify reason - "Degree from non-CRICOS institution" OR "Degree from non-Australian university" OR "Degree older than 5 years" (be specific)
- If "missing": Data not provided - "Institution type/location not specified"
- If "met": Clearly confirm - "Bachelor's degree from accredited Australian institution" ✓

**HEALTH REQUIREMENTS - Use Specific Tests/Standards (NEVER "meet health requirements" or generic terms):**
- "Medical examination by approved/licensed doctor" - Specify which doctors are acceptable
- "Chest X-ray for TB screening" - Specify requirement
- "Blood test (HIV, Hepatitis B, Hepatitis C, Syphilis)" - List specific diseases tested
- "No communicable disease (TB, Hepatitis B, Measles)" - List specific diseases excluded
- "Vaccinations required: [list specific vaccines]" - Not just "vaccinations"
- "Health screening for [specific condition]" - Be specific about what is screened
- "Medical costs (usually paid by applicant)" - Specify if applicable
- Example: "Medical examination by CMEC-approved doctor + chest X-ray + blood test (HIV, Hepatitis B, Syphilis)"
- Example: "No tuberculosis or other communicable disease + vaccination records (Measles, Polio minimum)"
- When "met": Confirm - "Medical examination passed + TB screening negative + no excludable diseases"

**❌ NEVER USE these generic phrases for health - they are NOT acceptable:**
- "Meet health requirements" ❌
- "Health requirements need to be met" ❌
- "Medical examination required" (without specifying what tests) ❌
- "Vaccinations required" (without listing which vaccines) ❌
- "No communicable disease" (without specifying which diseases) ❌
- Any health requirement without specific test names or conditions ❌

**CHARACTER REQUIREMENTS - Use Specific Standards (NEVER "meet character requirements" or generic terms):**
- "Police Clearance Certificate (PCC) from [country]" - Specify which countries required
- "PCC from all countries lived in for 6+ months" - Be specific about duration/countries
- "No criminal conviction for [specific crimes]" - Specify crime type:
  - "No conviction for crimes of violence"
  - "No drug-related offenses"
  - "No fraudulent/dishonesty offenses"
  - Or "No criminal conviction of any kind" if no exceptions
- "No fraud or misrepresentation in visa/immigration matters" (NOT just "truthful application")
- "No outstanding legal/court orders" (if applicable)
- "No immigration violations" (if applicable)
- Example: "Police Clearance Certificate from all countries where lived 6+ months + no criminal conviction for violence/fraud"
- Example: "PCC from Australia (if lived there) + PCC from UK (if lived there) + no visa fraud history"
- When "met": Confirm - "Police clearance received + no criminal record + no immigration violations"

**❌ NEVER USE these generic phrases - they are NOT acceptable:**
- "Meet character requirements" ❌
- "Character requirements need to be met" ❌
- "Police Check if applicable" ❌
- "Requirements need to be assessed" ❌
- Any requirement without specific details about WHAT is being checked ❌

**FUNDS REQUIREMENT - Use Specific Amounts:**
- "CAD $30,000 for principal applicant + $15,000 per dependent"
- "AUD $20,000+ in savings (proof required)"
- "GBP £2,000+ for living costs (per month if applicable)"
- "USD $10,000+ liquid assets" (NOT "sufficient funds")
- "Proof of funds (bank statement, sponsorship letter)" (specify source)
- Example: "CAD $30,000 in bank account (proof via bank statement)"

**EXCLUDE FROM REQUIREMENTS:**
- Do NOT include: "possible with exceptions", "special circumstances", "country resident exceptions"
- Do NOT include: "may be waived for", "in special cases", "under certain conditions"
- Do NOT include generic employer sponsorship unless it's a core visa requirement
- Do NOT mention "provincial nomination possible" as main requirement
- Focus ONLY on standard, baseline requirements that apply to typical applicants

**Match Score Calculation (STRICTLY FOLLOW):**

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

**VALIDATION:**
- If ALL requirements are "not_met" or "missing" → match_score MUST = 0
- If ZERO critical requirements met → match_score CANNOT exceed 30
- match_score range: 0-100

**Recommendation Guidelines:**
- If match_score < 30: "⚠️ Low eligibility. Focus on addressing critical gaps."
- If match_score 30-60: "⚠️ Moderate fit. Address missing critical requirements."
- If match_score 60-80: "✓ Good match. Address any remaining critical gaps."
- If match_score 80+: "✓ Strong match! Proceed with application."
- Always mention what missing information would improve the score

**Important:**
- Use ONLY official government sources (IRCC, Department of Home Affairs, UK Visas, etc.)
- Status options:
  * "met" = User clearly meets requirement
  * "not_met" = User clearly does NOT meet requirement
  * "missing" = Information not provided (treat as not met for scoring)
- **Keep all text CONCISE and SHORT:**
  * visa_requirement: Specific with numbers/test names/amounts (e.g. "IELTS 6.0 (min 6.0 in each band) OR TOEFL 60+ OR PTE 50+" NOT "proficient English")
  * user_value: Brief (e.g. "IELTS 7.0 overall" or "Not provided")
  * details: **CRITICAL** - Explain the status decision in one short sentence:
    - If "met": "✓ Masters in IT from recognized institution meets requirement"
    - If "not met": **ALWAYS explain WHY** - "❌ Masters from non-CRICOS institution does not meet requirement" OR "❌ Degree obtained 8 years ago (requirement: within 5 years)"
    - If "missing": "⚠️ Institution location/type not provided in chat history"
  * recommendation message: 1-2 short sentences max
  * next_steps: Short action items
- **For English Proficiency specifically:**
  * ALWAYS show all accepted test options with their exact score/band requirements
  * If band/section minimums apply (e.g., IELTS), mention them in details field
  * Include IELTS, TOEFL, PTE, Cambridge, CLB/CELPIP options when visa accepts them
- **IMPORTANT - Details Field Guidelines:**
  * When status = "not_met": ALWAYS state the specific reason (e.g., "Non-CRICOS", "Wrong field", "Too old", "Part-time not accepted")
  * When status = "missing": Note what information is needed (e.g., "Need to confirm institution type")
  * When status = "met": Confirm what matches (e.g., "Bachelor's from accredited Australian university ✓")
  * NEVER just say "Treated as not met" without explaining the reason in details field
- Return ONLY valid JSON, no markdown formatting
- NO markdown, NO extra text, ONLY JSON output

**FINAL QUALITY CHECK - BEFORE RETURNING JSON:**
Before returning your JSON response, verify that EVERY requirement meets these standards:
- ❌ If visa_requirement contains: "Meet X requirements" → FIX IT
- ❌ If visa_requirement contains: "Character requirements need to be met" → FIX IT
- ❌ If visa_requirement contains: "Health requirements" with no specific tests → FIX IT
- ❌ If visa_requirement is vague or doesn't specify exact amounts/scores/names → FIX IT
- ✅ Only proceed to return JSON when EVERY requirement is specific and actionable
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

        // Filter out generic exceptions and validate requirements
        $comparisonData = $this->filterGenericExceptions($comparisonData);

        // Validate and recalculate match scores to ensure accuracy
        $comparisonData = $this->validateAndRecalculateScores($comparisonData);

        return $comparisonData;
    }

    protected function filterGenericExceptions($comparisonData)
    {
        // List of generic exception phrases to exclude
        $genericExceptionPatterns = [
            '/possible with exceptions/i',
            '/special circumstances/i',
            '/country resident exceptions/i',
            '/may be waived/i',
            '/in special cases/i',
            '/under certain conditions/i',
            '/provincial nomination/i',
            '/possible if.*sponsor/i',
            '/sometimes waived/i',
            '/case by case/i',
            '/discretionary/i',
            '/at immigration discretion/i',
            '/spouse may/i',
            '/dependent may/i',
            '/family sponsorship possible/i',
            '/employer sponsorship possible/i',
        ];

        if (!isset($comparisonData['visa_options']) || !is_array($comparisonData['visa_options'])) {
            return $comparisonData;
        }

        foreach ($comparisonData['visa_options'] as &$visa) {
            if (!isset($visa['requirements']) || !is_array($visa['requirements'])) {
                continue;
            }

            // Filter requirements to remove those with generic exception language
            $filteredRequirements = [];
            foreach ($visa['requirements'] as $req) {
                $requirement = $req['visa_requirement'] ?? '';
                $details = $req['details'] ?? '';

                // Check if requirement contains generic exception patterns
                $hasGenericException = false;
                foreach ($genericExceptionPatterns as $pattern) {
                    if (preg_match($pattern, $requirement) || preg_match($pattern, $details)) {
                        $hasGenericException = true;
                        break;
                    }
                }

                // Only include if it doesn't contain generic exceptions
                if (!$hasGenericException) {
                    // Clean up requirement text to be more specific
                    $requirement = $this->cleanRequirementText($requirement);
                    $req['visa_requirement'] = $requirement;
                    $filteredRequirements[] = $req;
                }
            }

            $visa['requirements'] = $filteredRequirements;
        }

        return $comparisonData;
    }

    protected function cleanRequirementText($requirement)
    {
        // Convert vague terms to specific ones
        $replacements = [
            '/proficient.*english/i' => 'IELTS 6.0+ (or equivalent)',
            '/good.*english/i' => 'IELTS 6.0+ (or equivalent)',
            '/fluent.*english/i' => 'IELTS 7.0+ (or equivalent)',
            '/working knowledge/i' => 'Conversational level',
            '/some experience/i' => 'Minimum 2 years',
            '/extensive experience/i' => 'Minimum 5 years',
            '/relevant experience/i' => 'Relevant work experience',
            '/competitive salary/i' => 'Market-competitive salary',
            '/sufficient funds/i' => 'Financial proof of funds',
            '/adequate education/i' => 'Secondary education minimum',
            '/working age/i' => 'Under 45 years old',
            '/prime working age/i' => 'Between 25-55 years',
            '/young applicant/i' => 'Under 35 years old',
            '/established in field/i' => 'Minimum 5 years in occupation',
        ];

        $result = $requirement;
        foreach ($replacements as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $result);
        }

        return $result;
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

    public function update_profile_edit()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        // Handle both form data and JSON requests
        $requirement = $this->postParamValue('requirement', '');
        $userValue = $this->postParamValue('user_value', '');

        \Log::info('Initial attempt - postParamValue:', [
            'requirement' => $requirement,
            'user_value' => $userValue
        ]);

        // If empty, try to get from JSON body
        if (empty($requirement)) {
            $rawInput = file_get_contents('php://input');
            \Log::info('Raw input received:', ['input' => $rawInput, 'length' => strlen($rawInput)]);

            $jsonData = json_decode($rawInput, true);
            \Log::info('Parsed JSON:', ['data' => $jsonData, 'error' => json_last_error_msg()]);

            if (is_array($jsonData)) {
                $requirement = trim($jsonData['requirement'] ?? '');
                $userValue = trim($jsonData['user_value'] ?? '');
                \Log::info('Extracted from JSON:', ['requirement' => $requirement, 'user_value' => $userValue]);
            }
        }

        \Log::info('Profile edit received - FINAL:', [
            'requirement' => $requirement,
            'requirement_empty' => empty($requirement),
            'user_value' => $userValue,
            'member_id' => $this->_current_member['id']
        ]);

        if (empty($requirement)) {
            \Log::error('Requirement field is empty!');
            $this->pageResult(['status' => 400, 'message' => 'Requirement field is required']);
            return;
        }

        try {
            // Get existing edits from session using Laravel Session facade
            $sessionKey = implode('_', array_filter([
                '_' . $this->_mapping_data['module'],
                \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                'profile_edits'
            ]));
            $profileEdits = \Illuminate\Support\Facades\Session::get($sessionKey, []);
            \Log::info('Before save - Session edits:', ['edits' => $profileEdits]);

            // Update or add the edit
            $profileEdits[$requirement] = $userValue;

            // Save back to session using Laravel Session facade
            \Illuminate\Support\Facades\Session::put($sessionKey, $profileEdits);

            // Also add to chat log so Gemini sees the updated information
            $chatMessage = "I just updated my profile information - {$requirement}: {$userValue}";
            \DB::table('chat_log')->insert([
                'member_id' => $this->_current_member['id'],
                'type' => 'ask',
                'content' => $chatMessage,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            \Log::info('After save - Session edits:', ['edits' => $profileEdits]);
            \Log::info('Added to chat log:', ['message' => $chatMessage]);

            $this->pageResult([
                'status' => 200,
                'message' => 'Profile updated successfully',
                'edits' => $profileEdits
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile edit failed', [
                'error' => $e->getMessage(),
                'member_id' => $this->_current_member['id']
            ]);

            $this->pageResult([
                'status' => 500,
                'message' => 'Failed to save profile edit'
            ]);
        }
    }

    public function clear_profile_edits()
    {
        if (empty($this->_current_member)) {
            $this->pageResult(['status' => 403, 'message' => 'Please login first']);
            return;
        }

        try {
            $sessionKey = implode('_', array_filter([
                '_' . $this->_mapping_data['module'],
                \Illuminate\Support\Facades\Config::get('app_portal.application_uid'),
                'profile_edits'
            ]));
            \Illuminate\Support\Facades\Session::put($sessionKey, []);

            $this->pageResult([
                'status' => 200,
                'message' => 'Profile edits cleared'
            ]);
        } catch (\Exception $e) {
            $this->pageResult(['status' => 500, 'message' => 'Failed to clear edits']);
        }
    }

}

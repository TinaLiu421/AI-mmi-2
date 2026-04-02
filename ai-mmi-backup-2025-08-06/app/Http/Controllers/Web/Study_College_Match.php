<?php
namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\DB;

class Study_College_Match extends Home
{
    public function index()
    {
        $memberId = $this->_current_member['id'] ?? null;

        $prefs = null;
        if ($memberId) {
            $row = DB::table('study_preferences')->where('member_id', $memberId)->first();
            $prefs = $row ? (array)$row : null;
        }

        return $this->pageData(['prefs' => $prefs])->pageView('study_college_match');
    }

    public function save_preferences()
    {
        $memberId = $this->_current_member['id'] ?? null;
        if (empty($memberId)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in to save preferences.']);
            return;
        }

        $fields = [
            'choice_1_country', 'choice_1_city', 'choice_1_university', 'choice_1_level',
            'choice_1_fields', 'choice_1_budget', 'choice_1_year',
            'choice_2_country', 'choice_2_city', 'choice_2_university', 'choice_2_level',
            'choice_2_fields', 'choice_2_budget', 'choice_2_year',
            'choice_3_country', 'choice_3_city', 'choice_3_university', 'choice_3_level',
            'choice_3_fields', 'choice_3_budget', 'choice_3_year',
        ];

        $data = ['member_id' => $memberId, 'updated_at' => now()];
        foreach ($fields as $f) {
            $data[$f] = $this->getParamValue($f, '') ?: null;
        }

        $exists = DB::table('study_preferences')->where('member_id', $memberId)->first();
        if ($exists) {
            DB::table('study_preferences')->where('id', $exists->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('study_preferences')->insert($data);
        }

        $this->pageResult(['status' => 200, 'message' => 'Preferences saved.']);
    }

    public function find_matches()
    {
        $memberId = $this->_current_member['id'] ?? null;
        if (empty($memberId)) {
            $this->pageResult(['status' => 401, 'message' => 'Please log in.']);
            return;
        }

        // Load saved preferences
        $prefsRow = DB::table('study_preferences')->where('member_id', $memberId)->first();
        if (!$prefsRow) {
            $this->pageResult(['status' => 400, 'message' => 'Please save your study preferences first.']);
            return;
        }
        $prefs = (array)$prefsRow;

        // Load all registered institution profiles
        $institutions = DB::table('institution_profiles as ip')
            ->join('member as m', 'm.id', '=', 'ip.member_id')
            ->where('ip.status', 1)
            ->whereNull('ip.deleted_at')
            ->select('ip.id', 'ip.member_id', 'ip.institute_name', 'ip.programs', 'ip.admission',
                     'ip.fees', 'ip.summary', 'ip.key_dates', 'm.alias_name', 'm.avatar')
            ->orderBy('ip.id', 'asc')
            ->get()
            ->map(function($row) { return (array)$row; })
            ->toArray();

        // Build AI prompt
        $prefText = '';
        for ($c = 1; $c <= 3; $c++) {
            $country    = $prefs["choice_{$c}_country"]    ?? '';
            $city       = $prefs["choice_{$c}_city"]       ?? '';
            $university = $prefs["choice_{$c}_university"] ?? '';
            $level      = $prefs["choice_{$c}_level"]      ?? '';
            $fields     = $prefs["choice_{$c}_fields"]     ?? '';
            $budget     = $prefs["choice_{$c}_budget"]     ?? '';
            $year       = $prefs["choice_{$c}_year"]       ?? '';
            if ($country || $level || $fields) {
                $prefText .= "Choice {$c}: Country={$country}, City={$city}, University={$university}, "
                           . "Level={$level}, Fields={$fields}, Budget(USD)={$budget}, "
                           . "Year of enrolment={$year}\n";
            }
        }

        if (empty(trim($prefText))) {
            $this->pageResult(['status' => 400, 'message' => 'Please fill in at least one study preference choice.']);
            return;
        }

        $instText = '';
        foreach ($institutions as $inst) {
            $name     = $inst['alias_name'] ?: ($inst['institute_name'] ?? 'Unknown');
            $programs = $inst['programs']   ?? '';
            $admission= $inst['admission']  ?? '';
            $fees     = $inst['fees']       ?? '';
            $summary  = $inst['summary']    ?? '';
            $instText .= "---\nID: {$inst['id']}\nName: {$name}\n"
                       . "Programs: {$programs}\nAdmission: {$admission}\n"
                       . "Fees: {$fees}\nSummary: {$summary}\n";
        }

        $prompt = <<<PROMPT
You are an expert education advisor. A student has the following study preferences:

{$prefText}

Below are universities/colleges registered on our platform:

{$instText}

TASK:
1. REGISTERED MATCHES: Only include a registered institution if it genuinely matches the student's preferred country or city. Do NOT include institutions simply because they exist in our database. If none of the registered institutions match the student's location/preferences, return an empty matched array. For each genuine match, return a JSON object with:
   - institution_id (integer)
   - name (string)
   - match_score (integer 1-10)
   - match_reason (string, max 2 short sentences, max 35 words total: focus on the university's campus life, location, culture, facilities, student community, or city vibe — NOT about programs or fees, those are shown separately)
   - top_programs (array of up to 5 objects per program: {"name": "short program name", "admission": "key entry req in 1 line e.g. IELTS 6.5 · ATAR 85+", "fees": "~AUD 50,000/year"})
     IMPORTANT for top_programs: First check the institution profile data provided above. Use exact programs, admission requirements and fees from the database if available. For any program, admission requirement or fee value that is missing or unclear in the database, use your live web search to find the current accurate information directly from the institution's official website.

2. ALSO FOR CONSIDERATIONS: Using live web search, find 3-5 additional universities/colleges that best suit the student's preferences and are NOT in the registered list above. Use current, accurate data from each institution's official website. For each return: name (string), country (string), website (string, full URL e.g. https://www.unsw.edu.au), programs (array of up to 5 objects: {"name": "...", "fees": "~USD 30,000/year"} — fees must be current international student fees from the official site), why_recommended (string, ONE sentence max 15 words focusing on campus life, location or culture).

Return ONLY valid JSON in this exact format, no extra text:
{
  "matched": [
    {"institution_id": 1, "name": "...", "match_score": 9, "match_reason": "...", "top_programs": [{"name": "Bachelor of Engineering", "admission": "IELTS 6.5 · ATAR 85+", "fees": "~AUD 54,000/year"}]}
  ],
  "also_consider": [
    {"name": "...", "country": "...", "website": "https://...", "programs": [{"name": "Program A", "fees": "~USD 30,000/year"}], "why_recommended": "..."}
  ]
}
PROMPT;

        $result = $this->callXaiResponses($prompt, [
            'enable_search'     => true,
            'model'             => env('XAI_MODEL', 'grok-4-1-fast-reasoning'),
            'max_output_tokens' => 2048,
            'temperature'       => 0.3,
            'resume_thread'     => false,
        ]);

        if (empty($result['ok'])) {
            $this->pageResult(['status' => 500, 'message' => 'AI matching failed. Please try again.']);
            return;
        }

        // Parse AI JSON response
        $text = trim($result['text'] ?? '');
        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);
        // Extract JSON object
        if (preg_match('/\{[\s\S]*\}/u', $text, $jsonMatch)) {
            $text = $jsonMatch[0];
        }
        $parsed = json_decode($text, true);

        if (!is_array($parsed)) {
            \Log::error('Study_College_Match: Failed to parse AI JSON', ['text' => mb_substr($text, 0, 500)]);
            $this->pageResult(['status' => 500, 'message' => 'Could not parse AI response. Please try again.']);
            return;
        }

        // Enrich matched institutions with avatar/logo from DB records
        $instMap = [];
        foreach ($institutions as $inst) {
            $instMap[(int)$inst['id']] = $inst;
        }

        $matched = [];
        foreach (($parsed['matched'] ?? []) as $item) {
            $id   = (int)($item['institution_id'] ?? 0);
            $inst = $instMap[$id] ?? null;
            if (!$inst) continue;
            $logoUrl = '';
            if (!empty($inst['avatar'])) {
                if (file_exists(public_path('upload/member_logo/'.$inst['avatar']))) {
                    $logoUrl = asset('upload/member_logo/'.$inst['avatar']);
                } elseif (file_exists(public_path('upload/member_avatar/'.$inst['avatar']))) {
                    $logoUrl = asset('upload/member_avatar/'.$inst['avatar']);
                }
            }
            $matched[] = [
                'id'            => $id,
                'member_id'     => $inst['member_id'],
                'name'          => $inst['alias_name'] ?: ($inst['institute_name'] ?? ''),
                'logo_url'      => $logoUrl,
                'match_score'   => (int)($item['match_score']  ?? 0),
                'match_reason'  => $item['match_reason']       ?? '',
                'top_programs'  => array_slice((array)($item['top_programs'] ?? []), 0, 5),
                'profile_url'   => '/account/posts?uid='.$inst['member_id'],
            ];
        }

        $alsoConsider = [];
        foreach (($parsed['also_consider'] ?? []) as $item) {
            $alsoConsider[] = [
                'name'             => $item['name']             ?? '',
                'country'          => $item['country']          ?? '',
                'website'          => $item['website']          ?? '',
                'programs'         => array_slice((array)($item['programs'] ?? []), 0, 5),
                'why_recommended'  => $item['why_recommended']  ?? '',
            ];
        }

        $this->pageResult([
            'status'        => 200,
            'matched'       => $matched,
            'also_consider' => $alsoConsider,
        ]);
    }
}

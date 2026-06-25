<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;

/**
 * Admin management of education agent accounts.
 * Accessible only to hardcoded admin emails.
 * Routes (via RouteMapping): /en/Admin_Edu_Agents, /en/Admin_Edu_Agents/create_account, etc.
 */
class Admin_Edu_Agents extends WebController {

    private const ADMIN_EMAILS = ['admin@wealthskey.com', 'info@ai-mmi.com'];

    private function isAdmin(): bool {
        $currentEmail = mb_strtolower(trim((string)($this->_current_member['email'] ?? '')), 'UTF-8');
        if (in_array($currentEmail, self::ADMIN_EMAILS, true)) {
            return true;
        }

        // During full-account proxy mode, _current_member is the proxied account.
        $realAdminEmail = mb_strtolower(trim((string)$this->getSession('admin_real_email')), 'UTF-8');
        return in_array($realAdminEmail, self::ADMIN_EMAILS, true);
    }

    /**
     * List all education agent accounts (type=3, institution_type=2) + pending grants.
     * GET /en/Admin_Edu_Agents
     */
    public function index() {
        if (!$this->isAdmin()) {
            header('Location: ' . $this->toURL('account'));
            exit();
        }

        // Education agents: members type=3 joined with member_details institution_type=2
        $agents = DB::table('member as m')
            ->join('member_details as md', 'md.member_id', '=', 'm.id')
            ->where('m.type', 3)
            ->where('md.institution_type', 2)
            ->select(
                'm.id', 'm.full_name', 'm.email', 'm.status', 'm.verified',
                'm.created_at', 'md.company_name'
            )
            ->orderBy('m.created_at', 'desc')
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();

        // Pending grants (unclaimed pre-built accounts)
        $grants = DB::table('edu_agent_grants as g')
            ->join('member as m', 'm.id', '=', 'g.member_id')
            ->leftJoin('member_details as md', 'md.member_id', '=', 'm.id')
            ->select(
                'g.id as grant_id', 'g.token', 'g.status as grant_status',
                'g.created_at as grant_created_at', 'g.notes',
                'm.id as member_id', 'm.full_name', 'm.email',
                'md.company_name'
            )
            ->orderBy('g.created_at', 'desc')
            ->get()
            ->map(fn($r) => (array)$r)
            ->toArray();

        return $this->pageData([
            'agents' => $agents,
            'grants' => $grants,
        ])->pageView();
    }

    /**
     * Create a pre-built education agent account and generate a claim token.
     * POST /en/Admin_Edu_Agents/create_account
     * Body: company_name, notes (optional)
     */
    public function create_account() {
        if (!$this->isAdmin()) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $companyName = trim($this->_page_post_data['company_name'] ?? '');
        if (empty($companyName)) {
            return response()->json(['status' => 400, 'message' => 'Company name is required'], 400);
        }

        $notes = trim($this->_page_post_data['notes'] ?? '');

        DB::beginTransaction();
        try {
            $adminId = (int)($this->_current_member['id'] ?? 0);
            $placeholderEmail = 'unclaimed+' . time() . '-' . random_int(1000, 9999) . '@edu-claim.local';

            // 1. Create the member account (email left blank until claimed)
            $memberId = DB::table('member')->insertGetId([
                'method'       => 1,
                'full_name'    => $companyName,
                'alias_name'   => $companyName,
                'first_name'   => $companyName,
                'last_name'    => '',
                'email'        => $placeholderEmail,
                'password'     => '',
                'type'         => 3,
                'status'       => 0,   // inactive until claimed
                'verified'     => 0,
                'created_by'   => $adminId,
                'updated_by'   => $adminId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // 2. Create member_details with institution_type = 2 (education)
            DB::table('member_details')->insert([
                'member_id'        => $memberId,
                'company_name'     => $companyName,
                'institution_type' => 2,
                'status'           => 1,
                'created_by'       => $adminId,
                'updated_by'       => $adminId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // 3. Generate claim token
            $token = bin2hex(random_bytes(32)); // 64 hex chars
            DB::table('edu_agent_grants')->insert([
                'member_id'  => $memberId,
                'token'      => $token,
                'status'     => 0, // pending
                'created_by' => $adminId,
                'notes'      => $notes ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            $claimUrl = url('/claim_edu_account/' . $token);

            return response()->json([
                'status'    => 200,
                'message'   => 'Account created successfully',
                'claim_url' => $claimUrl,
                'member_id' => $memberId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('admin_edu_agents.create_account_failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json(['status' => 500, 'message' => 'Failed to create account'], 500);
        }
    }

    /**
     * Return the claim URL for an existing pending grant.
     * GET /en/Admin_Edu_Agents/get_claim_url/{grant_id}
     */
    public function get_claim_url($grantId = 0) {
        if (!$this->isAdmin()) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $grant = DB::table('edu_agent_grants')->where('id', (int)$grantId)->first();
        if (!$grant || (int)$grant->status !== 0) {
            return response()->json(['status' => 404, 'message' => 'Grant not found or already claimed'], 404);
        }

        $claimUrl = url('/claim_edu_account/' . $grant->token);
        return response()->json(['status' => 200, 'claim_url' => $claimUrl]);
    }

    /**
     * Revoke (delete) a pending grant and its unclaimed account.
     * POST /en/Admin_Edu_Agents/revoke_grant
     * Body: grant_id
     */
    public function revoke_grant() {
        if (!$this->isAdmin()) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $grantId = (int)($this->_page_post_data['grant_id'] ?? 0);
        $grant = DB::table('edu_agent_grants')->where('id', $grantId)->first();
        if (!$grant || (int)$grant->status !== 0) {
            return response()->json(['status' => 404, 'message' => 'Grant not found or already claimed'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('edu_agent_grants')->where('id', $grantId)->delete();
            // Also remove the unclaimed stub member and its details
            DB::table('member_details')->where('member_id', $grant->member_id)->delete();
            DB::table('member')->where('id', $grant->member_id)->delete();
            DB::commit();
            return response()->json(['status' => 200, 'message' => 'Grant revoked']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => 'Failed to revoke grant'], 500);
        }
    }

    /**
     * Proxy-edit an education agent's institution profile.
     * Redirects to Institution_Hub_Profile with target_member_id injected.
     * GET /en/Admin_Edu_Agents/edit_profile/{member_id}
     */
    public function edit_profile($targetMemberId = 0) {
        if (!$this->isAdmin()) {
            header('Location: ' . $this->toURL('account'));
            exit();
        }

        // Verify target is a valid education agent
        $target = DB::table('member')->where('id', (int)$targetMemberId)->first();
        $details = DB::table('member_details')->where('member_id', (int)$targetMemberId)->first();

        if (!$target || (int)$target->type !== 3 || !$details || (int)$details->institution_type !== 2) {
            header('Location: ' . $this->toURL('Admin_Edu_Agents'));
            exit();
        }

        // Pass target_member_id via session so Institution_Hub_Profile can pick it up
        session(['admin_proxy_member_id' => (int)$targetMemberId]);

        header('Location: ' . $this->toURL('institution_hub_profile'));
        exit();
    }

    /**
     * Start full-account access as the selected education member.
     * GET /en/Admin_Edu_Agents/access_full/{member_id}
     */
    public function access_full($targetMemberId = 0) {
        if (!$this->isAdmin()) {
            header('Location: ' . $this->toURL('account'));
            exit();
        }

        $targetMemberId = (int)$targetMemberId;
        $target = DB::table('member')->where('id', $targetMemberId)->first();
        $details = DB::table('member_details')->where('member_id', $targetMemberId)->first();

        if (!$target || (int)$target->type !== 3 || !$details || (int)$details->institution_type !== 2) {
            header('Location: ' . $this->toURL('Admin_Edu_Agents'));
            exit();
        }

        // Save real admin identity + proxy target in session.
        $this->setSession([
            'admin_real_member_id'      => (int)($this->_current_member['id'] ?? 0),
            'admin_real_email'          => (string)($this->_current_member['email'] ?? ''),
            'admin_proxy_member_id_full'=> $targetMemberId,
            'admin_proxy_member_id'     => $targetMemberId,
        ]);

        header('Location: ' . $this->toURL('account/posts'));
        exit();
    }

    /**
     * End full-account proxy mode and return to admin management page.
     * GET /en/Admin_Edu_Agents/stop_access
     */
    public function stop_access() {
        $this->delSession(['admin_proxy_member_id_full', 'admin_proxy_member_id', 'admin_real_email', 'admin_real_member_id']);
        header('Location: ' . $this->toURL('Admin_Edu_Agents'));
        exit();
    }

    /**
     * Create a full institution profile from a URL on behalf of the institution.
     * Creates a stub member + institution_profiles row, runs AI scrape, saves all data.
     * POST /en/Admin_Edu_Agents/create_from_url
     * Body: website_url (required), company_name (optional — AI will detect)
     */
    public function create_from_url() {
        if (!$this->isAdmin()) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $url = trim($this->_page_post_data['website_url'] ?? '');
        if (empty($url)) {
            return response()->json(['status' => 400, 'message' => 'Website URL is required'], 400);
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $url = preg_replace('/#.*$/', '', $url);
        $url = rtrim($url, '/');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'Invalid URL format'], 400);
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            return response()->json(['status' => 500, 'message' => 'AI service not configured'], 500);
        }

        @set_time_limit(480);

        $apiBase = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/');
        $model   = env('XAI_MODEL', 'grok-4-1-fast-reasoning');
        $domain  = parse_url($url, PHP_URL_HOST) ?: preg_replace('#^https?://#i', '', $url);
        $domain  = rtrim($domain, '/');

        // ── xAI helper functions ─────────────────────────────────────────────────
        $curlHeaders = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];

        $callXai = function(array $payload) use ($apiBase, $curlHeaders): array {
            $ch = curl_init("{$apiBase}/v1/responses");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => $curlHeaders,
                CURLOPT_TIMEOUT        => 150,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            return [$body, $code, $err];
        };

        $extractText = function(string $body): string {
            $decoded = json_decode($body, true);
            $raw = '';
            if (!empty($decoded['output'])) {
                foreach ($decoded['output'] as $out) {
                    if (!empty($out['content'])) {
                        foreach ($out['content'] as $c) {
                            if (($c['type'] ?? '') === 'output_text' && !empty($c['text'])) {
                                $raw = $c['text'];
                            }
                        }
                    }
                }
            }
            return $raw;
        };

        $parseJson = function(string $raw): array {
            if (empty($raw)) return [];
            $clean = trim(preg_replace('/^```json?\s*/i', '', preg_replace('/\s*```$/i', '', $raw)));
            $clean = preg_replace('/<grok:[^>]+>.*?<\/grok:[^>]+>/s', '', $clean);
            $clean = preg_replace('/<grok:[^>]+\/>/s', '', $clean);
            $parsed = json_decode($clean, true);
            if (is_array($parsed)) return $parsed;
            if (preg_match('/\{[\s\S]+\}/u', $clean, $m)) {
                $parsed = json_decode($m[0], true);
                if (is_array($parsed)) return $parsed;
            }
            return [];
        };

        $makePayload = function(string $prompt) use ($model): array {
            return [
                'model'             => $model,
                'input'             => [['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $prompt]]]],
                'temperature'       => 0,
                'max_output_tokens' => 8192,
                'tools'             => [['type' => 'web_search']],
                'tool_choice'       => 'auto',
            ];
        };

        $sharedRules = "OUTPUT: Return ONLY a valid JSON object. No code fences, no preamble, no text outside the JSON.\n"
            . "ALL data must come from live web searches — never use training memory for fees or specific figures.\n";

        // ── Call A: institute_name, summary, programs ─────────────────────────────
        $promptA = "You are a meticulous education data researcher with live web search capability.\n\n"
            . "BUILD A COMPLETE PROFILE of the institution at: {$url}\n\n"
            . "MANDATORY: Search 1: Visit {$url} homepage. "
            . "Search 2: site:{$domain} about history founded. "
            . "Search 3: site:{$domain} courses programs. "
            . "Search 4: {$domain} CRICOS RTO TEQSA accreditation. "
            . "Search 5: {$domain} programs qualifications 2025.\n\n"
            . $sharedRules
            . "Return JSON with 3 keys:\n"
            . "\"institute_name\": Full official legal name.\n"
            . "\"summary\": 250-350 word prose: name, type, founding year, campuses, CRICOS/RTO codes, student count, key areas, rankings.\n"
            . "\"programs\": Complete program list with ALL-CAPS category headings and '- ' per program.\n";

        \Log::info('create_from_url: call A', ['url' => $url]);
        [$bodyA, $codeA, $errA] = $callXai($makePayload($promptA));
        if ($errA || $codeA !== 200) {
            return response()->json(['status' => 500, 'message' => 'AI extraction failed (step 1). Please try again.'], 500);
        }
        $dataA = $parseJson($extractText($bodyA));
        $instituteName = trim($dataA['institute_name'] ?? '');
        $nameSearch = $instituteName ? "\"{$instituteName}\"" : "\"{$domain}\"";
        $nameOrDomain = $instituteName ?: $domain;

        // ── Call B: fees, admission, key_dates ───────────────────────────────────
        $promptB = "Extract FEES, ADMISSION REQUIREMENTS and KEY DATES for: {$nameOrDomain}\n"
            . "Website: {$url}\n\n"
            . "MANDATORY: Search 1: site:{$domain} fees tuition. "
            . "Search 2: {$nameSearch} tuition fees AUD 2025. "
            . "Search 3: {$nameSearch} admission entry requirements. "
            . "Search 4: {$nameSearch} IELTS PTE TOEFL English requirements. "
            . "Search 5: site:{$domain} intake dates 2025 2026.\n\n"
            . $sharedRules
            . "Return JSON with 3 keys: \"fees\", \"admission\", \"key_dates\". "
            . "Use ALL-CAPS headings + '- ' items. See institution type (university/VET/language school) and format accordingly.\n";

        \Log::info('create_from_url: call B', ['name' => $instituteName]);
        [$bodyB, $codeB, $errB] = $callXai($makePayload($promptB));
        $dataB = (!$errB && $codeB === 200) ? $parseJson($extractText($bodyB)) : [];

        // ── Call C: structured fields ─────────────────────────────────────────────
        $promptC = "Extract STRUCTURED DETAILS for: {$nameOrDomain} — {$url}\n\n"
            . "MANDATORY: Search 1: site:{$domain} contact address phone. "
            . "Search 2: site:{$domain} curriculum boarding bus scholarships year levels. "
            . "Search 3: {$nameSearch} exam boards IB VCE IGCSE NCEA curriculum. "
            . "Search 4: {$nameSearch} mission statement values school social media.\n\n"
            . $sharedRules
            . "Return JSON with keys:\n"
            . "\"city\": main campus suburb/city. \"address\": full street address. \"phone\": main phone.\n"
            . "\"school_phases\": year levels (e.g. 'Foundation – Year 12'). \"annual_fees_range\": e.g. 'AUD 28,000-36,000/yr'.\n"
            . "\"curriculum\": array of strings. \"exam_boards\": array. \"qualifications_awarded\": array.\n"
            . "\"student_teacher_ratio\": e.g. '8:1'. \"academic_year\": e.g. '4 terms'.\n"
            . "\"language_of_instruction\": array. \"has_boarding\": bool or null. \"has_school_bus\": bool or null.\n"
            . "\"has_scholarships\": bool or null. \"has_chinese_language_support\": bool or null. \"has_extra_languages\": bool or null.\n"
            . "\"mission_statement\": 1-3 sentence official statement. \"description\": 2-3 punchy profile sentences.\n"
            . "\"school_qualities\": array of 5-10 distinctive qualities. \"exam_results\": array of notable results.\n"
            . "\"social_links\": object with keys: facebook, instagram, youtube, linkedin, twitter, wechat (only if found).\n";

        \Log::info('create_from_url: call C');
        [$bodyC, $codeC, $errC] = $callXai($makePayload($promptC));
        $dataC = (!$errC && $codeC === 200) ? $parseJson($extractText($bodyC)) : [];

        $merged = array_merge($dataA, $dataB, $dataC);

        // ── Determine institution category ────────────────────────────────────────
        $categoryHint = strtolower($merged['institution_category'] ?? $merged['type'] ?? '');
        if (str_contains($categoryHint, 'uni')) $category = 'university';
        elseif (str_contains($categoryHint, 'vet') || str_contains($categoryHint, 'voc')) $category = 'vocational';
        elseif (str_contains($categoryHint, 'language')) $category = 'language_school';
        elseif (str_contains($categoryHint, 'high') || str_contains($categoryHint, 'secondary')) $category = 'highschool';
        elseif (str_contains($categoryHint, 'primary')) $category = 'primary_school';
        elseif (str_contains($categoryHint, 'intern')) $category = 'international_school';
        else $category = 'university'; // default

        // Derive from programs text if still unsure
        if ($category === 'university' && !empty($merged['programs'])) {
            $pText = strtolower($merged['programs']);
            if (str_contains($pText, 'certificate') || str_contains($pText, 'diploma') || str_contains($pText, 'rto')) $category = 'vocational';
            elseif (str_contains($pText, 'general english') || str_contains($pText, 'ielts preparation')) $category = 'language_school';
        }

        // ── Build the profile data array ──────────────────────────────────────────
        $companyName = $instituteName ?: trim($this->_page_post_data['company_name'] ?? 'New Institution');
        $adminId     = (int)($this->_current_member['id'] ?? 0);

        $profileData = [
            'website_url'          => $url,
            'institute_name'       => $instituteName ?: $companyName,
            'institution_category' => $category,
            'summary'              => (string)($merged['summary'] ?? ''),
            'programs'             => (string)($merged['programs'] ?? ''),
            'admission'            => (string)($merged['admission'] ?? ''),
            'fees'                 => (string)($merged['fees'] ?? ''),
            'key_dates'            => (string)($merged['key_dates'] ?? ''),
            'status'               => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];

        // Structured string fields
        $strFields = ['city','address','phone','school_phases','annual_fees_range',
            'student_teacher_ratio','academic_year','mission_statement','description'];
        foreach ($strFields as $f) {
            if (!empty($merged[$f]) && is_string($merged[$f])) {
                $profileData[$f] = trim($merged[$f]);
            }
        }

        // JSON array fields
        $arrFields = ['curriculum','exam_boards','qualifications_awarded',
            'language_of_instruction','school_qualities','exam_results'];
        foreach ($arrFields as $f) {
            if (!empty($merged[$f]) && is_array($merged[$f])) {
                $clean = array_values(array_filter(array_map('strval', $merged[$f])));
                if (!empty($clean)) $profileData[$f] = json_encode($clean, JSON_UNESCAPED_UNICODE);
            }
        }

        // Social links
        if (!empty($merged['social_links']) && is_array($merged['social_links'])) {
            $allowed = ['facebook','instagram','youtube','linkedin','twitter','wechat'];
            $social = [];
            foreach ($allowed as $k) {
                if (!empty($merged['social_links'][$k])) $social[$k] = (string)$merged['social_links'][$k];
            }
            if (!empty($social)) $profileData['social_links'] = json_encode($social, JSON_UNESCAPED_UNICODE);
        }

        // Boolean flags
        $boolFields = ['has_boarding','has_school_bus','has_scholarships',
            'has_chinese_language_support','has_extra_languages'];
        foreach ($boolFields as $f) {
            if (array_key_exists($f, $merged) && $merged[$f] !== null) {
                $profileData[$f] = $merged[$f] ? 1 : 0;
            }
        }

        // ── Create stub member + profile in DB ────────────────────────────────────
        DB::beginTransaction();
        try {
            // Check if a profile for this URL already exists
            $existingProfile = DB::table('institution_profiles')->where('website_url', $url)->first();
            if ($existingProfile) {
                DB::rollBack();
                $profileUrl = url('/en/institution_hub_profile/pub_view/' . $existingProfile->id);
                return response()->json([
                    'status'      => 409,
                    'message'     => 'A profile for this URL already exists.',
                    'profile_id'  => $existingProfile->id,
                    'profile_url' => $profileUrl,
                ], 409);
            }

            $placeholderEmail = 'unclaimed+' . time() . '-' . random_int(1000, 9999) . '@edu-claim.local';

            // Create stub member
            $memberId = DB::table('member')->insertGetId([
                'method'       => 1,
                'full_name'    => $companyName,
                'alias_name'   => $companyName,
                'first_name'   => $companyName,
                'last_name'    => '',
                'email'        => $placeholderEmail,
                'password'     => '',
                'type'         => 3,
                'status'       => 1,
                'verified'     => 0,
                'created_by'   => $adminId,
                'updated_by'   => $adminId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Create member_details
            DB::table('member_details')->insert([
                'member_id'        => $memberId,
                'company_name'     => $companyName,
                'institution_type' => 2,
                'status'           => 1,
                'created_by'       => $adminId,
                'updated_by'       => $adminId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Create claim token
            $claimToken = bin2hex(random_bytes(32));
            DB::table('edu_agent_grants')->insert([
                'member_id'  => $memberId,
                'token'      => $claimToken,
                'status'     => 0,
                'created_by' => $adminId,
                'notes'      => 'Auto-created from URL: ' . $url,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Save profile_strength
            $strCalc = [
                !empty($profileData['institute_name']),
                !empty($profileData['website_url']),
                !empty($profileData['description'] ?? $profileData['summary'] ?? ''),
                !empty($profileData['city'] ?? ''),
                !empty($profileData['phone'] ?? ''),
                !empty($profileData['annual_fees_range'] ?? ''),
                !empty($profileData['curriculum'] ?? ''),
                !empty($profileData['exam_boards'] ?? ''),
                !empty($profileData['mission_statement'] ?? ''),
                !empty($profileData['school_qualities'] ?? ''),
                !empty($profileData['admission'] ?? ''),
                !empty($profileData['fees'] ?? ''),
            ];
            $profileData['profile_strength'] = (int)round(array_sum(array_map('intval', $strCalc)) / count($strCalc) * 100);
            $profileData['member_id'] = $memberId;

            // Store claim token on the profile itself too
            if (DB::getSchemaBuilder()->hasColumn('institution_profiles', 'claim_token')) {
                $profileData['claim_token'] = $claimToken;
            }

            $profileId = DB::table('institution_profiles')->insertGetId($profileData);

            DB::commit();

            $claimUrl  = url('/claim_edu_account/' . $claimToken);
            $profileUrl = url('/en/institution_hub_profile/pub_view/' . $profileId);

            \Log::info('create_from_url: success', [
                'profile_id' => $profileId,
                'member_id'  => $memberId,
                'url'        => $url,
            ]);

            return response()->json([
                'status'      => 200,
                'message'     => 'Profile created successfully',
                'profile_id'  => $profileId,
                'member_id'   => $memberId,
                'claim_url'   => $claimUrl,
                'profile_url' => $profileUrl,
                'institute_name' => $companyName,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('create_from_url: DB failed', ['err' => $e->getMessage()]);
            return response()->json(['status' => 500, 'message' => 'Failed to save profile: ' . $e->getMessage()], 500);
        }
    }
}

<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Institution_Hub_Profile extends WebController {

    private const ADMIN_EMAILS = ['admin@wealthskey.com', 'info@ai-mmi.com'];
    private const PROGRAMS_COURSE_JSON_PREFIX = '__AIMMI_COURSES_JSON__:';
    private const GALLERY_FALLBACK_FILE_PREFIX = '_gallery_member_';

    private function _institutionProfileHasColumn($column): bool {
        static $columns = null;

        if ($columns === null) {
            try {
                $columns = Schema::getColumnListing('institution_profiles');
            } catch (\Throwable $e) {
                $columns = [];
            }
        }

        return in_array($column, $columns, true);
    }

    private function _decodeCoursesPayload($raw): array {
        if (!is_string($raw)) {
            return [];
        }

        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        // Accept either [course, ...] or { courses: [course, ...] }
        if (isset($decoded['courses']) && is_array($decoded['courses'])) {
            $decoded = $decoded['courses'];
        }

        if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
            return [];
        }

        $courses = [];
        foreach ($decoded as $course) {
            if (is_array($course) && !empty($course)) {
                $courses[] = $course;
            }
        }

        return $courses;
    }

    private function _extractCoursesFromProfile(array $profileArr): array {
        $courses = $this->_decodeCoursesPayload($profileArr['courses_json'] ?? '');
        if (!empty($courses)) {
            return $courses;
        }

        $programsRaw = (string)($profileArr['programs'] ?? '');
        if ($programsRaw === '') {
            return [];
        }

        if (strpos($programsRaw, self::PROGRAMS_COURSE_JSON_PREFIX) === 0) {
            $programsRaw = substr($programsRaw, strlen(self::PROGRAMS_COURSE_JSON_PREFIX));
        }

        return $this->_decodeCoursesPayload($programsRaw);
    }

    private function _encodeCoursesForProgramsFallback(array $courses): string {
        return self::PROGRAMS_COURSE_JSON_PREFIX . json_encode(array_values($courses));
    }

    private function _galleryFallbackPath(int $memberId): string {
        return public_path('upload/inst_gallery/' . self::GALLERY_FALLBACK_FILE_PREFIX . $memberId . '.json');
    }

    private function _loadGalleryFallback(int $memberId): array {
        $path = $this->_galleryFallbackPath($memberId);
        if (!file_exists($path)) {
            return [];
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $gallery = [];
        foreach ($decoded as $file) {
            $file = basename((string)$file);
            if ($file !== '' && preg_match('/^[a-zA-Z0-9_\-.]+$/', $file)) {
                $gallery[] = $file;
            }
        }

        return array_values(array_unique($gallery));
    }

    private function _saveGalleryFallback(int $memberId, array $gallery): void {
        $dir = public_path('upload/inst_gallery');
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        $clean = [];
        foreach ($gallery as $file) {
            $file = basename((string)$file);
            if ($file !== '' && preg_match('/^[a-zA-Z0-9_\-.]+$/', $file)) {
                $clean[] = $file;
            }
        }

        @file_put_contents($this->_galleryFallbackPath($memberId), json_encode(array_values(array_unique($clean))));
    }

    private function _extractGalleryFromProfile(array $profileArr, int $memberId): array {
        $gallery = [];
        $raw = (string)($profileArr['gallery_json'] ?? '');

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                foreach ($decoded as $file) {
                    $file = basename((string)$file);
                    if ($file !== '' && preg_match('/^[a-zA-Z0-9_\-.]+$/', $file)) {
                        $gallery[] = $file;
                    }
                }
            }
        }

        if (empty($gallery)) {
            $gallery = $this->_loadGalleryFallback($memberId);
        }

        return array_values(array_unique($gallery));
    }

    /** Returns true if the currently logged-in user is a hardcoded admin. */
    private function _isAdmin(): bool {
        return !empty($this->_current_member)
            && in_array($this->_current_member['email'] ?? '', self::ADMIN_EMAILS);
    }

    /**
     * Returns the member_id of the education agent whose profile should be operated on.
     * For admins: reads admin_proxy_member_id from session (set by Admin_Edu_Agents::edit_profile).
     * For regular users: returns their own id.
     */
    private function _getProxyMemberId(): int {
        if ($this->_isAdmin()) {
            $proxyId = (int)session('admin_proxy_member_id', 0);
            return $proxyId > 0 ? $proxyId : (int)($this->_current_member['id'] ?? 0);
        }
        return (int)($this->_current_member['id'] ?? 0);
    }

    /**
     * Public view of an institution profile (no login required).
     * URL: /institution_hub_profile/pub_view/{profile_id}
     */
    public function pub_view($profile_id = 0) {
        $profile_id = (int)$profile_id;
        if (!$profile_id) abort(404);

        $profile = DB::table('institution_profiles as ip')
            ->join('member as m', 'm.id', '=', 'ip.member_id')
            ->leftJoin('member_details as md', 'md.member_id', '=', 'ip.member_id')
            ->where('ip.id', $profile_id)
            ->where('ip.status', 1)
            ->whereNull('ip.deleted_at')
            ->select('ip.*', 'm.avatar', 'm.alias_name', 'md.institution_type as _member_institution_type')
            ->first();

        if (!$profile) abort(404);

        $profileArr = (array)$profile;

        // Prefer per-profile logo over shared member.avatar (critical for admin-proxy profiles)
        if (!empty($profileArr['logo'])) {
            $profileArr['avatar'] = $profileArr['logo'];
        }

        // Derive institution_category from member_details.institution_type when not explicitly set
        if (empty($profileArr['institution_category'])) {
            $instType = (int)($profileArr['_member_institution_type'] ?? 0);
            if ($instType === 2) {
                // Education institution — try to infer subcategory; default to vocational for now
                $profileArr['institution_category'] = 'vocational';
            }
        }
        unset($profileArr['_member_institution_type']);
        $profileArr['gallery_json'] = json_encode($this->_extractGalleryFromProfile($profileArr, (int)($profileArr['member_id'] ?? 0)));

        $courses = $this->_extractCoursesFromProfile($profileArr);
        if (!empty($courses)) {
            $profileArr['courses_json'] = json_encode($courses);
        }

        // Derive institute name
        if (empty($profileArr['institute_name'])) {
            $profileArr['institute_name'] = $profileArr['alias_name'] ?? 'Institution';
        }

        $this->pageMeta([
            'title'       => $profileArr['institute_name'] . ' — Programs & Courses',
            'description' => mb_substr(strip_tags($profileArr['summary'] ?? ''), 0, 160, 'UTF-8'),
        ]);

        return $this->pageData([
            'profile' => $profileArr,
            'courses' => $courses,
        ])->pageView('institution_hub_profile_view');
    }

    /**
     * Show the institution profile setup / edit page.
     * URL: /institution_hub_profile  (GET)
     */
    public function index() {
        // Must be logged in as an education institution OR be an admin
        // Guests see a login-prompt page instead of a hard redirect
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return $this->pageData(['is_guest' => true])->pageView('institution_hub_profile');
        }

        $member_id = $this->_getProxyMemberId();
        $proxyMember = $this->_isAdmin() && $member_id !== (int)$this->_current_member['id']
            ? (array)(DB::table('member')->where('id', $member_id)->first() ?? [])
            : $this->_current_member;

        // Fetch institution_type from member details
        $details = DB::table('member_details')->where('member_id', $member_id)->first();
        $institution_type = $details ? (int)$details->institution_type : 1;

        if ($institution_type !== 2 && !$this->_isAdmin()) {
            // Not an education institution — redirect to account
            header('Location: ' . $this->toURL('account'));
            exit();
        }

        // Load existing profile if any
        $profile = DB::table('institution_profiles')->where('member_id', $member_id)->first();
        $profileArr = $profile ? (array)$profile : [];
        $profileArr['gallery_json'] = json_encode($this->_extractGalleryFromProfile($profileArr, $member_id));
        $courses = $this->_extractCoursesFromProfile($profileArr);
        if (!empty($courses)) {
            $profileArr['courses_json'] = json_encode($courses);
        }

        return $this->pageData([
            'profile'          => $profileArr,
            'member'           => $proxyMember,
            'institution_type' => $institution_type,
            'is_admin_proxy'   => $this->_isAdmin() && $member_id !== (int)$this->_current_member['id'],
            'proxy_member_id'  => $member_id,
        ])->pageView();
    }

    /**
     * Scrape a website URL using xAI and return structured profile sections.
     * URL: /institution_hub_profile/scrape  (POST)
     */
    public function scrape() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $url = trim($this->_page_post_data['website_url'] ?? '');
        if (empty($url)) {
            return response()->json(['status' => 400, 'message' => 'Website URL is required'], 400);
        }

        // Auto-prepend https:// if the user omitted the scheme (e.g. "university.edu.au")
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        // Strip hash fragments — they are client-side routing only (e.g. /#/home on SPAs)
        // and are irrelevant / confusing for web search
        $url = preg_replace('/#.*$/', '', $url);
        $url = rtrim($url, '/');

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'Invalid URL format'], 400);
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            return response()->json(['status' => 500, 'message' => 'AI service not configured'], 500);
        }

        // Allow plenty of time — web search + large JSON output can take 2–4 minutes
        @set_time_limit(360);

        $apiBase = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/');
        $model   = env('XAI_MODEL', 'grok-4-1-fast-reasoning');

        // -----------------------------------------------------------------------
        // STRATEGY: Two sequential calls.
        //   Call A → institute_name + summary + programs  (searches by URL)
        //   Call B → fees + admission + key_dates         (searches by NAME from A,
        //             which gives far better results than searching by URL alone)
        //
        // Prompts are institution-type adaptive — they work for universities,
        // VET/RTO providers, language schools, colleges, etc.
        // -----------------------------------------------------------------------

        // Extract domain early — needed in both prompts for site: searches
        $domain = parse_url($url, PHP_URL_HOST) ?: preg_replace('#^https?://#i', '', $url);
        $domain = preg_replace('#/$#', '', $domain);

        $sharedRules = "OUTPUT FORMATTING (plain text — renders inside a <textarea>):\n"
            . "- Section headings in ALL-CAPS followed by a colon and a newline. Example: TUITION FEES:\n"
            . "- Each item on its own line starting with '- ' (dash space)\n"
            . "- Blank line between every section\n"
            . "- No markdown (no **, ##, *, _), no numbered lists, no HTML tags\n"
            . "- Newlines inside JSON string values must be escaped as \\n (literal backslash + n)\n\n"
            . "DATA ACCURACY — CRITICAL RULES:\n"
            . "- NEVER use training memory for fees, dates, IELTS/PTE scores, or program-specific data — ALL figures must come from live searches performed in THIS session\n"
            . "- NEVER fabricate data, fill in 'typical' values, or make assumptions even for well-known institutions\n"
            . "- NEVER write values like 'varies', 'contact institution', or leave a field blank if you haven't searched — always perform additional targeted searches first\n"
            . "- If a fee or score is a RANGE, state both ends: e.g. 'IELTS 5.5–6.5 depending on course'\n"
            . "- Always include the source year: 'AUD 32,000 per year (2025)' not just 'AUD 32,000'\n"
            . "- If data genuinely cannot be found after exhaustive searching, write: '- [Item]: Contact admissions for current details'\n"
            . "- NEVER write '(check website)' — that is useless to students\n\n"
            . "FALLBACK DATABASES FOR AUSTRALIAN INSTITUTIONS (MANDATORY before any fallback message):\n"
            . "- training.gov.au — complete RTO course registry (Certs, Diplomas, vocational quals)\n"
            . "- cricos.teqsa.gov.au — CRICOS courses with registered international student fees\n"
            . "- myskills.gov.au — VET course information and provider details\n"
            . "- teqsa.gov.au/national-register — university and HEP registration\n"
            . "- studyaustralia.gov.au — official fees and entry requirements\n"
            . "NOTE: Many institution websites use JavaScript/SPA routing (URLs with /#/) — their subpages CANNOT be scraped. For those sites, government databases above are MANDATORY substitutes.\n\n";

        $promptA = "You are a meticulous education data researcher with live web search capability.\n\n"
            . "BUILD A COMPLETE PROFILE of the institution at this URL: {$url}\n\n"
            . "MANDATORY RESEARCH — you MUST perform ALL EIGHT searches below before writing any JSON:\n"
            . "Search 1: Visit \"{$url}\" — read the homepage. Record the full official institution name, type (university/college/RTO/language school), all campus cities, and any accreditation or registration codes shown. NOTE: If this site uses #/ routing (SPA), the individual pages are NOT accessible by web search — rely on external sources for detailed data.\n"
            . "Search 2: site:{$domain} about history founded — find founding year, student population, international student percentage, accreditations, and rankings.\n"
            . "Search 3: site:{$domain} courses programs qualifications — get the COMPLETE list of every qualification, course, or program this institution offers.\n"
            . "Search 4: \"{$domain}\" CRICOS TEQSA RTO accreditation registration — find all official codes: CRICOS Provider Code, RTO National Code, TEQSA PRV number, NEAS accreditation. Check authoritative sources (training.gov.au, teqsa.gov.au, cricos.teqsa.gov.au).\n"
            . "Search 5: training.gov.au provider \"{$domain}\" courses qualifications registered — search the Australian government's national training register for the COMPLETE official list of all registered courses (this works even if the institution website is a SPA).\n"
            . "Search 6: cricos.teqsa.gov.au \"{$domain}\" courses registered international — check the CRICOS database for all registered courses available to international students, including CRICOS course codes.\n"
            . "Search 7: institute name \"{$domain}\" courses programs offered complete list 2025 site:studyaustralia.gov.au OR site:studymoves.com OR site:hotcoursesabroad.com — find additional course listings from aggregators.\n"
            . "Search 8: \"{$domain}\" programs qualifications offered 2025 — cross-check and add any programs missed in earlier searches.\n\n"
            . "INSTITUTION TYPE — determine from your searches which category applies:\n"
            . "UNIVERSITY: awards Bachelor/Master/PhD, accredited by TEQSA\n"
            . "VET/RTO/TAFE: awards Certificates/Diplomas (AQF 1-6), registered with ASQA or a State authority\n"
            . "LANGUAGE SCHOOL (ELICOS): primarily English-language programs; NEAS or ELICOS accredited\n"
            . "COLLEGE/MIXED: holds both CRICOS and RTO registration, or offers both degrees and vocational courses\n\n"
            . $sharedRules
            . "RETURN a single valid JSON object with exactly 3 keys. No code fences, no preamble, no text outside the JSON.\n\n"
            . "\"institute_name\": The full official legal/registered name as it appears on the homepage or About page. Not the domain. Not abbreviated. Examples: 'Australian Institute of Business and Technology', 'University of Melbourne'.\n\n"
            . "\"summary\": Write EXACTLY 3 paragraphs totalling 300-400 words (NO bullet lists, flowing prose only). Paragraph 1 (identity): official name, institution type (university/VET/language school/college), founding year, city/state, ALL campus locations, CRICOS Provider Code, RTO National Code, TEQSA PRV number (whichever apply), total student enrolment, and international student percentage. Paragraph 2 (programs): main study areas and disciplines, AQF levels or degree types offered, key qualifications, industry focus, and any notable academic specialisations. Paragraph 3 (experience): student support services, facilities, industry partnerships, graduate employment outcomes, notable rankings or achievements, and why this institution is a strong choice for international students.\n\n"
            . "\"programs\": The COMPLETE list of ALL programs — do NOT truncate, do NOT write '...and more'. Group under ALL-CAPS category headings that match this institution type:\n"
            . "VET/RTO: CERTIFICATE I: | CERTIFICATE II: | CERTIFICATE III: | CERTIFICATE IV: | DIPLOMA: | ADVANCED DIPLOMA: | GRADUATE DIPLOMA:\n"
            . "University: one heading per Faculty/School (e.g. FACULTY OF BUSINESS:) then list all degree levels under it\n"
            . "Language school: GENERAL ENGLISH: | IELTS PREPARATION: | TOEFL PREPARATION: | ENGLISH FOR ACADEMIC PURPOSES: | BUSINESS ENGLISH: | HOLIDAY PROGRAMS: | JUNIOR PROGRAMS:\n"
            . "Per program line: '- [Full official qualification name] ([National code if found])'\n"
            . "VET example: '- Certificate III in Commercial Cookery (SIT30821)'\n"
            . "University example: '- Bachelor of Business (3 years full-time)'\n"
            . "Language example: '- General English — Beginner to Advanced (10-30 hrs/week)'";

        $curlHeaders = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        // Helper: make a single xAI API call, returns [body, httpCode, curlError]
        $callXai = function(array $payload) use ($apiBase, $curlHeaders): array {
            $ch = curl_init("{$apiBase}/v1/responses");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => $curlHeaders,
                CURLOPT_TIMEOUT        => 130,
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

        // Helper: extract the last output_text from an xAI /v1/responses body
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

        // Helper: parse JSON from model output, stripping code fences / preamble
        $parseJson = function(string $raw): array {
            if (empty($raw)) return [];
            $clean = trim($raw);
            $clean = preg_replace('/^```json?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/i', '', $clean);
            // Strip grok citation/render tags
            $clean = preg_replace('/<grok:[^>]+>.*?<\/grok:[^>]+>/s', '', $clean);
            $clean = preg_replace('/<grok:[^>]+\/>/s', '', $clean);
            // Attempt 1: direct parse
            $parsed = json_decode($clean, true);
            if (is_array($parsed)) return $parsed;
            // Attempt 2: extract outermost JSON object
            if (preg_match('/\{[\s\S]+\}/u', $clean, $m)) {
                $parsed = json_decode($m[0], true);
                if (is_array($parsed)) return $parsed;
            }
            // Attempt 3: repair literal newlines inside JSON string values
            $repaired = preg_replace_callback(
                '/"((?:[^"\\\\]|\\\\.)*)"/s',
                function ($match) {
                    return '"' . str_replace(["\r\n", "\r", "\n"], '\\n', $match[1]) . '"';
                },
                $clean
            );
            $parsed = json_decode($repaired, true);
            if (is_array($parsed)) return $parsed;
            return [];
        };

        $makePayload = function(string $prompt) use ($model): array {
            return [
                'model'             => $model,
                'input'             => [[
                    'role'    => 'user',
                    'content' => [['type' => 'input_text', 'text' => $prompt]],
                ]],
                'temperature'       => 0,
                'max_output_tokens' => 8192,
                'tools'             => [['type' => 'web_search']],
                'tool_choice'       => 'required',
            ];
        };

        // ---- Call A: institute_name, summary, programs ----
        \Log::info('Institution profile scrape: starting call A', ['url' => $url]);
        [$bodyA, $codeA, $errA] = $callXai($makePayload($promptA));
        \Log::info('Institution profile scrape: call A done', ['code' => $codeA, 'err' => $errA]);

        if ($errA || $codeA !== 200) {
            \Log::error('Institution profile scrape: call A failed', ['code' => $codeA, 'err' => $errA, 'body' => substr((string)$bodyA, 0, 300)]);
            return response()->json(['status' => 500, 'message' => 'AI extraction failed on step 1. Please try again.'], 500);
        }

        $rawTextA = $extractText($bodyA);
        $dataA = $parseJson($rawTextA);
        if (empty($dataA)) {
            \Log::warning('Institution profile scrape: call A JSON parse failed', ['raw' => substr($rawTextA, 0, 400)]);
        }
        // Fallback: if institute_name is missing, try a simple regex extraction from raw text
        if (empty($dataA['institute_name']) && !empty($rawTextA)) {
            if (preg_match('/"institute_name"\s*:\s*"([^"]{3,150})"/i', $rawTextA, $nm)) {
                $dataA['institute_name'] = $nm[1];
            }
        }

        // Use the real institution name (from Call A) in Call B's searches —
        // searching by name returns far richer results than searching by URL alone
        $instituteName = trim($dataA['institute_name'] ?? '');
        // Build a combined search term: name + URL domain for specificity
        $parsed_url   = parse_url($url);
        $domain       = $parsed_url['host'] ?? $url;
        $nameSearch   = $instituteName ? "\"{$instituteName}\"" : "\"{$domain}\"";
        $nameOrDomain = $instituteName ?: $domain;

        $promptB = "You are a meticulous education data researcher with live web search capability.\n\n"
            . "BUILD THE COMPLETE FEES, ADMISSION AND DATES PROFILE for: {$nameOrDomain}\n"
            . "Institution website: {$url}\n\n"
            . "MANDATORY RESEARCH — you MUST perform ALL TEN searches below before writing any JSON:\n"
            . "Search 1: site:{$domain} fees tuition cost international — find the official current fee schedule directly on the institution website.\n"
            . "Search 2: {$nameSearch} international student tuition fees AUD 2025 2026 — verify and supplement fee data from external sources.\n"
            . "Search 3: {$nameSearch} domestic student fees government subsidised concession VET Student Loans HECS-HELP — find domestic fee structure.\n"
            . "Search 4: cricos.teqsa.gov.au {$nameSearch} course fees international — check the CRICOS database for registered international student fee data (tuition fee as registered with government).\n"
            . "Search 5: training.gov.au {$nameSearch} course fees registered qualifications — check the national training register for fee data and all registered qualifications.\n"
            . "Search 6: {$nameSearch} tuition fee AUD 2025 \"per week\" OR \"per year\" OR \"per course\" OR \"per semester\" — broad fee search to capture any pricing format.\n"
            . "Search 7: site:{$domain} admission apply entry requirements how to enrol — find official admission criteria and application steps. NOTE: if site uses #/ SPA routing, check external sources.\n"
            . "Search 8: {$nameSearch} IELTS TOEFL PTE Academic Cambridge Duolingo English language entry requirement minimum score 2025 — find exact English proficiency scores. Also check studyaustralia.gov.au and CRICOS.\n"
            . "Search 9: site:{$domain} intake dates term dates semester start enrolment 2025 2026 — find ALL upcoming study start dates and deadlines.\n"
            . "Search 10: {$nameSearch} intake 2025 2026 semester term start date application deadline enrolment — cross-check dates from external education aggregators and the institution's social media.\n\n"
            . "INSTITUTION TYPE: Determine from searches whether this is a University, VET/RTO, Language School, or Mixed College. Adapt ALL three output fields accordingly.\n\n"
            . $sharedRules
            . "RETURN a single valid JSON object with exactly 3 keys. No code fences, no preamble, no text outside the JSON.\n\n"
            . "\"fees\": ALL fees found. Use ALL-CAPS section headings:\n"
            . "TUITION / COURSE FEES:\n"
            . "  Per program: '- [Program name]: [Currency] [amount] per [year/course/week/term] ([year])'\n"
            . "  Examples: '- Certificate IV in IT Networking: AUD 5,500 per course (2025)'\n"
            . "  '- General English (20hrs/week): AUD 340 per week (2025)'\n"
            . "  '- Bachelor of Business: AUD 34,000 per year (2025)'\n"
            . "APPLICATION / ENROLMENT FEE:\n"
            . "MATERIAL / RESOURCE FEES:\n"
            . "LIVING COSTS:\n"
            . "SCHOLARSHIPS AND DISCOUNTS:\n"
            . "  Each entry: '- [Scholarship name]: [amount or %], [eligibility], [deadline if found]'\n"
            . "PAYMENT OPTIONS:\n\n"
            . "\"admission\": ALL entry requirements. Use ALL-CAPS section headings:\n"
            . "ENGLISH LANGUAGE REQUIREMENTS:\n"
            . "  Format: '- [Test name]: [minimum overall score] overall ([component score if stated])'\n"
            . "  Example: '- IELTS Academic: 5.5 overall (no band below 5.0)'\n"
            . "  List ALL accepted tests: IELTS, TOEFL iBT, PTE Academic, Cambridge, Duolingo, institutional English test.\n"
            . "ACADEMIC / ENTRY REQUIREMENTS:\n"
            . "  Exact minimum qualification, GPA, ATAR, age, or work experience per course level.\n"
            . "  CRITICAL: Requirements must be specific and INTERNALLY CONSISTENT. If Certificate IV requires Year 12, do NOT also write 'no requirements'. Be specific per level.\n"
            . "  Example: '- Certificate III courses: Year 10 completion or equivalent'\n"
            . "  Example: '- Certificate IV courses: Year 12 or equivalent; minimum age 18'\n"
            . "  Example: '- Master of Engineering: Bachelor in Engineering, GPA 5.0/7.0 minimum'\n"
            . "AGE REQUIREMENTS:\n"
            . "RECOGNITION OF PRIOR LEARNING (RPL):\n"
            . "APPLICATION PROCESS:\n"
            . "  Step-by-step: how to apply, documents required, processing time\n"
            . "INTERNATIONAL STUDENT REQUIREMENTS:\n"
            . "  Student visa subclass, OSHC requirement, COE process, required documents\n\n"
            . "\"key_dates\": ALL intake, semester, and deadline dates. Use ALL-CAPS headings matching the institution:\n"
            . "VET/RTO: INTAKE DATES: / TERM START DATES: / ENROLMENT DEADLINES:\n"
            . "University: SEMESTER 1 2025: / SEMESTER 2 2025: / SEMESTER 1 2026: (include application close date, orientation, teaching start)\n"
            . "Language school: WEEKLY INTAKE: or TERM START DATES: (list each month), HOLIDAY CLOSURE PERIODS:\n"
            . "SCHOLARSHIP DEADLINES:\n"
            . "OPEN DAYS AND INFORMATION SESSIONS:\n"
            . "Always include the year in each date. Example: '- Term 1 2026 starts: 2 February 2026'\n"
            . "If rolling enrolment applies, state explicitly: '- Rolling enrolment: new students start every Monday'";

        // ---- Call B: fees, admission, key_dates (using real institution name) ----
        \Log::info('Institution profile scrape: starting call B', ['name' => $instituteName, 'url' => $url]);
        [$bodyB, $codeB, $errB] = $callXai($makePayload($promptB));
        \Log::info('Institution profile scrape: call B done', ['code' => $codeB, 'err' => $errB]);

        if ($errB || $codeB !== 200) {
            \Log::error('Institution profile scrape: call B failed', ['code' => $codeB, 'err' => $errB, 'body' => substr((string)$bodyB, 0, 300)]);
            $dataB = [];
        } else {
            $dataB = $parseJson($extractText($bodyB));
            if (empty($dataB)) {
                \Log::warning('Institution profile scrape: call B JSON parse failed', ['raw' => substr((string)$bodyB, 0, 400)]);
            }
        }

        // ---- Call C: structured fields (city, phone, curriculum, etc.) ----
        $promptC = "You are a meticulous education data researcher with live web search capability.\n\n"
            . "EXTRACT STRUCTURED DETAILS for the institution at: {$url}\n"
            . "Institution name: {$nameOrDomain}\n\n"
            . "MANDATORY RESEARCH — perform ALL SIX searches below before writing any JSON:\n"
            . "Search 1: site:{$domain} contact address phone email — find street address, suburb/city, phone number.\n"
            . "Search 2: site:{$domain} about curriculum year levels boarding bus scholarships — find school phases (e.g. K-12, Year 7-12), boarding, transport, scholarships info.\n"
            . "Search 3: {$nameSearch} IBDP A-Level IGCSE VCE HSC Queensland Curriculum AQF — identify the exam boards and curriculum offered.\n"
            . "Search 4: {$nameSearch} mission statement values school qualities social media — find mission statement, school values/qualities, and official social media profiles.\n"
            . "Search 5: training.gov.au {$nameSearch} provider address contact phone — find official contact details from the national training register if the website is a SPA or contact page is inaccessible.\n"
            . "Search 6: cricos.teqsa.gov.au {$nameSearch} provider address scholarships — find scholarships and address from official CRICOS/TEQSA records.\n\n"
            . "RETURN a single valid JSON object. No code fences, no preamble, no text outside the JSON.\n\n"
            . "Return ALL of the following keys (use null or empty array if data not found):\n"
            . "\"city\": (string) Suburb or city where the main campus is located. E.g. 'South Yarra, Melbourne'\n"
            . "\"address\": (string) Full street address of main campus. E.g. '123 Example Road, South Yarra VIC 3141'\n"
            . "\"phone\": (string) Main contact phone number in international format if possible. E.g. '+61 3 9876 5432'\n"
            . "\"school_phases\": (string) Year levels or phases offered. E.g. 'Foundation to Year 12', 'ELC to Year 12', 'Years 7-12', 'Certificate I to IV'\n"
            . "\"annual_fees_range\": (string) Brief annual tuition range. E.g. 'AUD 28,000 – AUD 36,000 per year (2025)', 'AUD 6,500 – AUD 12,000 per course (2025)'. Omit if highly variable.\n"
            . "\"cost_of_living\": (string) Estimated monthly or annual cost of living for a student in this city. E.g. 'AUD 1,800 – AUD 2,500 per month', 'AUD 21,000 per year'. Include housing, food, transport.\n"
            . "\"registration_number\": (string) Official registration or provider code. E.g. 'CRICOS 00116K', 'RTO 0132', 'TEQSA PRV12079'. Look for CRICOS, RTO, ABN, company registration.\n"
            . "\"intakes\": (string) Main intake periods. E.g. 'February, July', 'January, April, July, October', 'Rolling intake'. List months only, comma-separated.\n"
            . "\"visa_requirements\": (string) Key student visa requirements. E.g. 'Student visa (subclass 500), OSHC, financial capacity proof' for Australia. Keep to 1-2 sentences.\n"
            . "\"curriculum\": (array of strings) Official curriculum frameworks. E.g. [\"International Baccalaureate\", \"Victorian Curriculum\", \"VCE\"]\n"
            . "\"exam_boards\": (array of strings) Examination authorities. E.g. [\"IBO\", \"VCAA\", \"NESA\", \"QCAA\"]\n"
            . "\"qualifications_awarded\": (array of strings) Main qualifications. E.g. [\"IB Diploma\", \"VCE\", \"IBCP\", \"Bachelor of Business\", \"Certificate IV\"]\n"
            . "\"student_teacher_ratio\": (string) E.g. '8:1', '12:1'. Null if not found.\n"
            . "\"academic_year\": (string) How the year is structured. E.g. '4 terms', '2 semesters', 'Rolling enrolment'\n"
            . "\"language_of_instruction\": (array of strings) E.g. [\"English\"] or [\"English\", \"Mandarin\"]\n"
            . "\"has_boarding\": (boolean or null) true if boarding facilities are available, false if explicitly not available, null if unclear\n"
            . "\"has_school_bus\": (boolean or null) true if school bus/shuttle service is available, false if not, null if unclear\n"
            . "\"has_scholarships\": (boolean or null) true if any scholarship programs exist, false if explicitly none, null if unclear\n"
            . "\"has_chinese_language_support\": (boolean or null) true if Chinese language support staff or programs are available\n"
            . "\"has_extra_languages\": (boolean or null) true if LOTE (Languages Other Than English) programs are offered\n"
            . "\"mission_statement\": (string) The institution's official mission or vision statement (1-3 sentences). Null if not found.\n"
            . "\"description\": (string) 3-5 sentence engaging description for a profile card. Must highlight: what makes this institution distinctive, its location, institution type and accreditations, and why international students choose it. Write in present tense, energetic but professional tone.\n"
            . "\"school_qualities\": (array of strings) 6-10 distinctive qualities or selling points specific to THIS institution — not generic statements. E.g. [\"Small class sizes with 12:1 student ratio\", \"Top 50 QS World University ranking\", \"CRICOS-registered for international students\", \"Campuses in Melbourne, Sydney and Brisbane\"]\n"
            . "\"exam_results\": (array of strings) Notable exam results/rankings. E.g. [\"IB median score 38/45 (2024)\", \"Top 10 VCE school in Victoria (2024)\"]\n"
            . "\"social_links\": (object) Social media URLs with lowercase keys. Supported keys: facebook, instagram, youtube, linkedin, twitter, wechat. Only include keys where a valid URL was found.\n";

        \Log::info('Institution profile scrape: starting call C', ['name' => $nameOrDomain]);
        [$bodyC, $codeC, $errC] = $callXai($makePayload($promptC));
        \Log::info('Institution profile scrape: call C done', ['code' => $codeC, 'err' => $errC]);

        $dataC = [];
        if (!$errC && $codeC === 200) {
            $dataC = $parseJson($extractText($bodyC));
            if (empty($dataC)) {
                \Log::warning('Institution profile scrape: call C JSON parse failed', ['raw' => substr((string)$bodyC, 0, 400)]);
            }
        } else {
            \Log::warning('Institution profile scrape: call C failed (non-fatal)', ['code' => $codeC, 'err' => $errC]);
        }

        // Merge all three calls — C fields only augment, they don't override A/B text blobs
        $merged = array_merge($dataA, $dataB, $dataC);

        // Text blob fields (Call A + B)
        $textFields = ['institute_name', 'programs', 'admission', 'fees', 'summary', 'key_dates'];
        $result = [];
        foreach ($textFields as $f) {
            $result[$f] = isset($merged[$f]) ? (string)$merged[$f] : '';
        }
        $result['website_url'] = $url;

        // Structured string fields (Call C)
        $structuredStrings = ['city', 'address', 'phone', 'school_phases', 'annual_fees_range',
            'student_teacher_ratio', 'academic_year', 'mission_statement', 'description',
            'cost_of_living', 'registration_number', 'intakes', 'visa_requirements'];
        foreach ($structuredStrings as $f) {
            if (isset($merged[$f]) && is_string($merged[$f])) {
                $result[$f] = trim($merged[$f]);
            }
        }

        // JSON array fields (Call C) — sanitize to flat string arrays
        $arrayFields = ['curriculum', 'exam_boards', 'qualifications_awarded', 'language_of_instruction',
            'school_qualities', 'exam_results'];
        foreach ($arrayFields as $f) {
            $val = $merged[$f] ?? null;
            if (is_array($val)) {
                $cleaned = array_values(array_filter(array_map('strval', $val)));
                $result[$f . '_json'] = json_encode($cleaned, JSON_UNESCAPED_UNICODE);
            }
        }

        // Social links (JSON object)
        if (!empty($merged['social_links']) && is_array($merged['social_links'])) {
            $allowed = ['facebook','instagram','youtube','linkedin','twitter','wechat','bilibili'];
            $social = [];
            foreach ($allowed as $k) {
                if (!empty($merged['social_links'][$k])) {
                    $social[$k] = (string)$merged['social_links'][$k];
                }
            }
            if (!empty($social)) {
                $result['social_links_json'] = json_encode($social, JSON_UNESCAPED_UNICODE);
            }
        }

        // Boolean flags (Call C) — convert to 0/1/null
        $boolFields = ['has_boarding', 'has_school_bus', 'has_scholarships',
            'has_chinese_language_support', 'has_extra_languages'];
        foreach ($boolFields as $f) {
            if (array_key_exists($f, $merged) && $merged[$f] !== null) {
                $result[$f] = $merged[$f] ? 1 : 0;
            }
        }

        // === Call D: Gallery photo scraping ===
        $instNameForGallery = $result['institute_name'] ?? '';
        $galleryFiles = [];
        try {
            $galleryPrompt = "You are a research assistant. Find 4 high-quality, publicly accessible image URLs from the official website or reputable news/press sources for the institution named: \"{$instNameForGallery}\".\n\nRequirements:\n- Images must be campus buildings, facilities, classrooms, or official campus life photos (NOT logo images, NOT student portraits)\n- URLs must be direct links to image files (.jpg, .jpeg, .png, .webp)\n- Must be publicly accessible without login\n- Prefer images from the institution's own website\n\nReturn JSON ONLY in this exact format:\n{\"gallery\": [\"URL1\", \"URL2\", \"URL3\", \"URL4\"]}";

            [$bodyD, $codeD, $errD] = $callXai($makePayload($galleryPrompt));
            if ($codeD === 200) {
                $galleryResp = $parseJson($extractText($bodyD));
                if (!empty($galleryResp['gallery']) && is_array($galleryResp['gallery'])) {
                    $galleryDir = public_path('upload/inst_gallery');
                    if (!file_exists($galleryDir)) @mkdir($galleryDir, 0755, true);
                    $member_id_g = $this->_getProxyMemberId();
                    foreach ($galleryResp['gallery'] as $imgUrl) {
                        if (!filter_var($imgUrl, FILTER_VALIDATE_URL)) continue;
                        if (!preg_match('/^https?:\/\//i', $imgUrl)) continue;
                        $ch = curl_init($imgUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_TIMEOUT        => 12,
                            CURLOPT_CONNECTTIMEOUT => 6,
                            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; GalleryFetcher/1.0)',
                            CURLOPT_SSL_VERIFYPEER => false,
                        ]);
                        $imgData = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        $ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                        curl_close($ch);
                        if ($httpCode !== 200 || empty($imgData) || strlen($imgData) < 10240) continue;
                        if (!preg_match('#image/(jpeg|png|webp|jpg)#i', $ctype)) continue;
                        $ext = preg_match('#image/png#i', $ctype) ? 'png' : (preg_match('#image/webp#i', $ctype) ? 'webp' : 'jpg');
                        $fname = 'g_' . $member_id_g . '_' . time() . '_' . count($galleryFiles) . '.' . $ext;
                        if (file_put_contents($galleryDir . '/' . $fname, $imgData) !== false) {
                            $galleryFiles[] = $fname;
                        }
                        if (count($galleryFiles) >= 4) break;
                    }
                }
            }
        } catch (\Exception $eG) {
            \Log::warning('Gallery scraping failed (non-fatal)', ['err' => $eG->getMessage()]);
        }
        if (!empty($galleryFiles)) {
            $result['gallery'] = $galleryFiles;
        }

        return response()->json(['status' => 200, 'data' => $result]);
    }

    /**
     * Fetch the real institution logo from their website.
     * Tries: Clearbit → Google favicon (sz=256) → apple-touch-icon → common logo paths.
     * NOTE: og:image is intentionally excluded — it is nearly always a campus/people photo.
     * URL: /institution_hub_profile/generate_logo  (POST)
     */
    public function generate_logo() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $websiteUrl = trim($this->_page_post_data['website_url'] ?? '');
        // Strip SPA hash fragments (e.g. /#/home) — the server never sees them anyway
        $websiteUrl = preg_replace('/#.*$/', '', $websiteUrl);
        $websiteUrl = rtrim($websiteUrl, '?&');
        if (empty($websiteUrl) || !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'A valid website URL is required to fetch the logo.'], 400);
        }

        $member_id = $this->_getProxyMemberId();
        $location  = public_path('upload/member_logo');
        if (!file_exists($location)) {
            @mkdir($location, 0755, true);
        }

        $parsed  = parse_url($websiteUrl);
        $host    = $parsed['host'] ?? '';
        $domain  = preg_replace('/^www\./i', '', $host);
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . $host;

        $imageData   = null;
        $contentType = '';

        // Helper: download a URL and return [body, http_code, content_type]
        $fetch = function($url, $timeout = 10) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_TIMEOUT         => $timeout,
                CURLOPT_CONNECTTIMEOUT  => min($timeout, 5),  // cap connection time
                CURLOPT_USERAGENT       => 'Mozilla/5.0 (compatible; LogoFetcher/1.0)',
                CURLOPT_SSL_VERIFYPEER  => false,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            return [$body, $code, $ct];
        };

        // $minBytes: minimum byte count; $checkSize: reject confirmed tiny (<=32px) images
        $isLogoImage = function($body, $ct, $minBytes = 100, $checkSize = false) {
            if (empty($body) || strlen($body) < $minBytes) return false;
            // SVG always accepted (scales infinitely)
            if (strpos($body, '<svg') !== false || strpos($ct, 'svg') !== false) return true;
            $isBinary = (strpos($ct, 'image') !== false)
                || substr($body, 0, 4) === "\x89PNG"
                || substr($body, 0, 3) === "\xFF\xD8\xFF"
                || substr($body, 0, 6) === 'GIF87a' || substr($body, 0, 6) === 'GIF89a'
                || (substr($body, 0, 4) === 'RIFF' && substr($body, 8, 4) === 'WEBP')
                || strpos($ct, 'icon') !== false                          // .ico files
                || substr($body, 0, 4) === "\x00\x00\x01\x00";            // .ico magic bytes
            if (!$isBinary) return false;
            // Only reject confirmed tiny images when explicitly requested (path probing)
            if ($checkSize) {
                $size = @getimagesizefromstring($body);
                if ($size && isset($size[0], $size[1]) && $size[0] > 0 && $size[1] > 0
                    && ($size[0] <= 32 || $size[1] <= 32)) {
                    return false;
                }
            }
            return true;
        };

        // --- Method 1: Scrape homepage — institution's own logo is most accurate ---
        [$html] = $fetch($websiteUrl, 10);
        if (!empty($html)) {
            $logoUrl = null;

            // apple-touch-icon (brand icon, always square, suitable as logo)
            if (preg_match('/<link[^>]+rel=["\']apple-touch-icon(?:-precomposed)?["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
                $logoUrl = $m[1];
            }
            if (!$logoUrl && preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']apple-touch-icon(?:-precomposed)?["\'][^>]*>/i', $html, $m)) {
                $logoUrl = $m[1];
            }

            // <img> tag whose src/class/id/alt contains "logo"
            if (!$logoUrl) {
                // src-first pattern
                if (preg_match('/<img[^>]+src=["\']([^"\']+\.(png|svg|jpg|jpeg|gif|webp))["\'][^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]*>/i', $html, $m)) {
                    $logoUrl = $m[1];
                }
                // attribute-first pattern
                if (!$logoUrl && preg_match('/<img[^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+\.(png|svg|jpg|jpeg|gif|webp))["\'][^>]*>/i', $html, $m)) {
                    $logoUrl = $m[1];
                }
                // Any img inside an element with "logo" in href/class/id (e.g. <a class="logo"><img src=...>)
                if (!$logoUrl && preg_match('/<(?:a|div|span|figure)[^>]+(?:class|id)=["\'][^"\']*logo[^"\']*["\'][^>]*>\s*<img[^>]+src=["\']([^"\']+\.(png|svg|jpg|jpeg|gif|webp))["\'][^>]*>/i', $html, $m)) {
                    $logoUrl = $m[1];
                }
            }

            // Largest icon in <link rel="icon"> with sizes attribute (e.g. 192x192)
            if (!$logoUrl) {
                preg_match_all('/<link[^>]+rel=["\']icon["\'][^>]+sizes=["\'](\d+)x\d+["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER);
                if (!empty($matches)) {
                    usort($matches, function($a, $b) { return (int)$b[1] - (int)$a[1]; });
                    $logoUrl = $matches[0][2];
                }
                if (!$logoUrl) {
                    preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]+sizes=["\'](\d+)x\d+["\'][^>]+rel=["\']icon["\'][^>]*>/i', $html, $matches2, PREG_SET_ORDER);
                    if (!empty($matches2)) {
                        usort($matches2, function($a, $b) { return (int)$b[2] - (int)$a[2]; });
                        $logoUrl = $matches2[0][1];
                    }
                }
            }

            // <link rel="shortcut icon"> — last resort from homepage
            if (!$logoUrl && preg_match('/<link[^>]+rel=["\']shortcut icon["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
                $logoUrl = $m[1];
            }

            if (!empty($logoUrl)) {
                // Resolve relative URL
                if (strpos($logoUrl, '//') === 0) {
                    $logoUrl = ($parsed['scheme'] ?? 'https') . ':' . $logoUrl;
                } elseif (strpos($logoUrl, '/') === 0) {
                    $logoUrl = $baseUrl . $logoUrl;
                } elseif (strpos($logoUrl, 'http') !== 0) {
                    $logoUrl = $baseUrl . '/' . $logoUrl;
                }
                [$body, $code, $ct] = $fetch($logoUrl);
                if ($code === 200 && $isLogoImage($body, $ct, 100)) {
                    $imageData   = $body;
                    $contentType = $ct;
                }
            }
        }

        // --- Method 2: Clearbit Logo API (fallback — good for well-known brands) ---
        if (empty($imageData) && !empty($domain)) {
            [$body, $code, $ct] = $fetch("https://logo.clearbit.com/{$domain}?size=600&format=png");
            if ($code === 200 && $isLogoImage($body, $ct, 100)) {
                $imageData   = $body;
                $contentType = 'image/png';
            }
        }

        // --- Method 3: Probe common logo file paths ---
        if (empty($imageData)) {
            $paths = [
                '/apple-touch-icon.png',
                '/apple-touch-icon-precomposed.png',
                '/favicon-256x256.png',
                '/favicon-192x192.png',
                '/favicon-96x96.png',
                '/images/logo.png',
                '/img/logo.png',
                '/assets/images/logo.png',
                '/assets/img/logo.png',
                '/assets/logo.png',
                '/assets/logo.svg',
                '/static/logo.png',
                '/static/logo.svg',
                '/static/images/logo.png',
                '/public/logo.png',
                '/public/logo.svg',
                '/logo.png',
                '/logo.svg',
                '/favicon.ico',
            ];
            foreach ($paths as $path) {
                [$body, $code, $ct] = $fetch($baseUrl . $path, 4);
                // checkSize=true: skip confirmed 32px-or-smaller favicons in this fallback path
                if ($code === 200 && $isLogoImage($body, $ct, 100, true)) {
                    $imageData   = $body;
                    $contentType = $ct;
                    break;
                }
            }
        }

        // --- Method 4: Web App Manifest — best source for SPA / PWA sites (React, Vue, Angular) ---
        // SPAs render logos via JS so the HTML shell has no logo tags, but they always
        // ship a manifest.json listing high-res icons (192x192, 512x512).
        if (empty($imageData)) {
            // Look for <link rel="manifest"> in already-fetched HTML
            $manifestUrl = null;
            if (!empty($html)) {
                if (preg_match('/<link[^>]+rel=["\']manifest["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
                    $manifestUrl = $m[1];
                }
                if (!$manifestUrl && preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']manifest["\'][^>]*>/i', $html, $m)) {
                    $manifestUrl = $m[1];
                }
            }
            // Resolve relative manifest URL
            if (!empty($manifestUrl)) {
                if (strpos($manifestUrl, '//') === 0)         $manifestUrl = ($parsed['scheme'] ?? 'https') . ':' . $manifestUrl;
                elseif (strpos($manifestUrl, '/') === 0)      $manifestUrl = $baseUrl . $manifestUrl;
                elseif (strpos($manifestUrl, 'http') !== 0)   $manifestUrl = $baseUrl . '/' . $manifestUrl;
            }
            // Also try standard fallback paths regardless
            $manifestCandidates = array_values(array_filter(array_unique([
                $manifestUrl,
                $baseUrl . '/manifest.json',
                $baseUrl . '/site.webmanifest',
                $baseUrl . '/manifest.webmanifest',
            ])));
            foreach ($manifestCandidates as $mUrl) {
                [$mBody, $mCode, $mCt] = $fetch($mUrl, 5);
                if ($mCode !== 200 || empty($mBody)) continue;
                $manifest = json_decode($mBody, true);
                if (empty($manifest) || !is_array($manifest)) continue;
                $icons = $manifest['icons'] ?? [];
                if (empty($icons)) continue;
                // Sort by size descending — pick largest available icon
                usort($icons, function($a, $b) {
                    $sa = isset($a['sizes']) ? (int)explode('x', strtolower($a['sizes']))[0] : 0;
                    $sb = isset($b['sizes']) ? (int)explode('x', strtolower($b['sizes']))[0] : 0;
                    return $sb - $sa;
                });
                foreach ($icons as $icon) {
                    $iconSrc = $icon['src'] ?? '';
                    if (empty($iconSrc)) continue;
                    // Resolve relative URL
                    if (strpos($iconSrc, '//') === 0)        $iconSrc = ($parsed['scheme'] ?? 'https') . ':' . $iconSrc;
                    elseif (strpos($iconSrc, '/') === 0)     $iconSrc = $baseUrl . $iconSrc;
                    elseif (strpos($iconSrc, 'http') !== 0)  $iconSrc = $baseUrl . '/' . $iconSrc;
                    [$body, $code, $ct] = $fetch($iconSrc);
                    if ($code === 200 && $isLogoImage($body, $ct, 100)) {
                        $imageData   = $body;
                        $contentType = $ct;
                        break 2; // stop searching manifests
                    }
                }
            }
        }

        // --- Method 5: Bing image search — finds logo from image index (website + Google Images) ---
        if (empty($imageData)) {
            $instituteName = trim($this->_page_post_data['institute_name'] ?? '');
            $searchTerm    = $instituteName ?: $domain;
            $q = urlencode($searchTerm . ' official logo');
            $headers = [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                'Accept-Language: en-US,en;q=0.9',
            ];
            $ch = curl_init("https://www.bing.com/images/search?q={$q}&qft=+filterui:imagesize-large&first=1&form=IRFLTR");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_TIMEOUT         => 10,
                CURLOPT_CONNECTTIMEOUT  => 5,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_SSL_VERIFYPEER  => false,
            ]);
            $bingHtml = curl_exec($ch);
            curl_close($ch);

            if (!empty($bingHtml)) {
                // Bing HTML-encodes the JSON: &quot;murl&quot;:&quot;URL&quot; — decode before regex
                $bingDecoded = html_entity_decode($bingHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                preg_match_all('/"murl"\s*:\s*"(https?:[^"]+\.(?:png|jpg|jpeg|svg|webp)[^"]{0,300})"/i', $bingDecoded, $mUrls);
                $candidates = array_slice($mUrls[1] ?? [], 0, 10);
                foreach ($candidates as $imgUrl) {
                    // Unescape JSON unicode/amp sequences
                    $imgUrl = @json_decode('"' . str_replace('"', '\\"', $imgUrl) . '"') ?: $imgUrl;
                    if (empty($imgUrl) || !filter_var($imgUrl, FILTER_VALIDATE_URL)) continue;
                    [$body, $code, $ct] = $fetch($imgUrl, 10);
                    if ($code === 200 && $isLogoImage($body, $ct, 100)) {
                        $imageData   = $body;
                        $contentType = $ct;
                        break;
                    }
                }
            }
        }

        // --- Method 6: DuckDuckGo instant image search ---
        if (empty($imageData)) {
            $instituteName = trim($this->_page_post_data['institute_name'] ?? '');
            $searchTerm    = $instituteName ?: $domain;
            $q = urlencode($searchTerm . ' logo');
            // DuckDuckGo image search token request
            $ch = curl_init("https://duckduckgo.com/?q={$q}&ia=images&iax=images");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; LogoFetcher/1.0)',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_COOKIEJAR      => '/tmp/ddg_cookie.txt',
                CURLOPT_COOKIEFILE     => '/tmp/ddg_cookie.txt',
            ]);
            $ddgHtml = curl_exec($ch);
            curl_close($ch);
            if (!empty($ddgHtml) && preg_match('/vqd=([\d-]+)/', $ddgHtml, $vqdMatch)) {
                $vqd = $vqdMatch[1];
                $ch2 = curl_init("https://duckduckgo.com/i.js?q={$q}&vqd={$vqd}&f=size%3ALarge,type%3Aphoto&p=1&l=us-en");
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; LogoFetcher/1.0)',
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_COOKIEFILE     => '/tmp/ddg_cookie.txt',
                    CURLOPT_HTTPHEADER     => ['Referer: https://duckduckgo.com/'],
                ]);
                $ddgJson = curl_exec($ch2);
                curl_close($ch2);
                if (!empty($ddgJson)) {
                    $ddgData = json_decode($ddgJson, true);
                    $results = $ddgData['results'] ?? [];
                    foreach (array_slice($results, 0, 5) as $r) {
                        $imgUrl = $r['image'] ?? '';
                        if (empty($imgUrl)) continue;
                        [$body, $code, $ct] = $fetch($imgUrl, 10);
                        if ($code === 200 && $isLogoImage($body, $ct, 100)) {
                            $imageData   = $body;
                            $contentType = $ct;
                            break;
                        }
                    }
                }
            }
        }

        // --- Method 7: Google favicon — last resort (may be small/blurry but better than nothing) ---
        if (empty($imageData) && !empty($domain)) {
            [$body, $code, $ct] = $fetch("https://www.google.com/s2/favicons?domain={$domain}&sz=512");
            if ($code === 200 && $isLogoImage($body, $ct, 50)) {
                $imageData   = $body;
                $contentType = $ct ?: 'image/png';
            }
        }

        if (empty($imageData)) {
            return response()->json(['status' => 500, 'message' => 'Could not retrieve logo from the institution website. Please upload a logo manually.'], 500);
        }

        // If the image is small, still use it but attach a warning in the response.
        // SVG is always fine (resolution-independent).
        $logoWarning = null;
        $isSvgData   = strpos($imageData, '<svg') !== false || strpos($contentType, 'svg') !== false;
        if (!$isSvgData) {
            $sizeInfo = @getimagesizefromstring($imageData);
            if ($sizeInfo && isset($sizeInfo[0], $sizeInfo[1])
                && $sizeInfo[0] > 0 && $sizeInfo[1] > 0
                && ($sizeInfo[0] < 48 || $sizeInfo[1] < 48)) {
                $logoWarning = 'The logo found is too small (' . $sizeInfo[0] . '×' . $sizeInfo[1] . 'px). For best quality, please upload a logo manually.';
            }
        }

        // Determine extension
        $ext = 'png';
        if (strpos($contentType, 'svg') !== false || strpos($imageData, '<svg') !== false) $ext = 'svg';
        elseif (strpos($contentType, 'jpeg') !== false || strpos($contentType, 'jpg') !== false) $ext = 'jpg';
        elseif (strpos($contentType, 'gif') !== false) $ext = 'gif';
        elseif (strpos($contentType, 'webp') !== false) $ext = 'webp';
        elseif (strpos($contentType, 'icon') !== false || substr($imageData, 0, 4) === "\x00\x00\x01\x00") $ext = 'ico';
        // Detect from magic bytes when content-type is ambiguous
        if ($ext === 'png' && substr($imageData, 0, 3) === "\xFF\xD8\xFF") $ext = 'jpg';
        if ($ext === 'png' && (substr($imageData, 0, 6) === 'GIF87a' || substr($imageData, 0, 6) === 'GIF89a')) $ext = 'gif';

        // .ico: convert to PNG via GD so browsers display it consistently
        if ($ext === 'ico') {
            $tmpIco = sys_get_temp_dir() . '/logo_ico_' . $member_id . '_' . time() . '.ico';
            file_put_contents($tmpIco, $imageData);
            $converted = false;
            try {
                $pngData = \Intervention\Image\Facades\Image::make($tmpIco)->encode('png', 95)->getEncoded();
                $imageData   = $pngData;
                $contentType = 'image/png';
                $ext         = 'png';
                $converted   = true;
            } catch (\Throwable $e) { /* Intervention may not support .ico */ }
            if (!$converted) {
                // Fallback: GD imagecreatefromstring (PHP 8.1+ supports some .ico layouts)
                $gdImg = @imagecreatefromstring($imageData);
                if ($gdImg) {
                    ob_start();
                    imagepng($gdImg, null, 9);
                    $pngData = ob_get_clean();
                    imagedestroy($gdImg);
                    if (!empty($pngData)) {
                        $imageData   = $pngData;
                        $contentType = 'image/png';
                        $ext         = 'png';
                        $converted   = true;
                    }
                }
            }
            @unlink($tmpIco);
            // If conversion truly failed, save as-is (modern browsers render .ico)
            if (!$converted) $ext = 'png'; // rename anyway; content is .ico but browser copes
        }

        $fileName = 'logo_' . $member_id . '_' . time() . '.' . $ext;
        $savePath = $location . '/' . $fileName;

        if (!file_put_contents($savePath, $imageData)) {
            return response()->json(['status' => 500, 'message' => 'Failed to save logo.'], 500);
        }

        // Resize — preserve aspect ratio, never upscale (skip SVG)
        // Upscaling low-res images (e.g. favicons) produces blurry output, so we
        // only downscale images that exceed 600px; smaller images are kept as-is.
        if ($ext !== 'svg') {
            try {
                \Intervention\Image\Facades\Image::make($savePath)
                    ->resize(600, 600, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize(); // never enlarge — prevents blurry upscaling
                    })
                    ->save($savePath, 95);
            } catch (\Throwable $e) {
                // Keep original if resize fails
            }
        }

        return response()->json([
            'status'    => 200,
            'logo_url'  => 'upload/member_logo/' . $fileName,
            'logo_file' => $fileName,
            'warning'   => $logoWarning,
        ]);
    }

    /**
     * Manual logo upload — supports PNG, JPG, GIF, WEBP, SVG.
     * URL: /institution_hub_profile/upload_logo  (POST, multipart)
     */
    public function upload_logo() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $file = \Illuminate\Support\Facades\Request::file('logo_file');
        if (empty($file) || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid file uploaded.'], 400);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
        if (!in_array($ext, $allowed, true)) {
            return response()->json(['status' => 400, 'message' => 'Unsupported file type. Allowed: PNG, JPG, GIF, WEBP, SVG.'], 400);
        }

        // 5 MB limit
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'File too large. Maximum size is 5 MB.'], 400);
        }

        $member_id = $this->_getProxyMemberId();
        $location  = public_path('upload/member_logo');
        if (!file_exists($location)) {
            @mkdir($location, 0755, true);
        }

        $fileName = 'logo_' . $member_id . '_' . time() . '.' . $ext;
        $savePath = $location . '/' . $fileName;

        if (!$file->move($location, $fileName)) {
            return response()->json(['status' => 500, 'message' => 'Failed to save uploaded file.'], 500);
        }

        // Resize raster images (skip SVG — it is resolution-independent)
        if ($ext !== 'svg') {
            try {
                \Intervention\Image\Facades\Image::make($savePath)
                    ->resize(600, 600, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->save($savePath, 95);
            } catch (\Throwable $e) {
                // Keep original if resize fails
            }
        }

        return response()->json([
            'status'    => 200,
            'logo_url'  => 'upload/member_logo/' . $fileName,
            'logo_file' => $fileName,
        ]);
    }

    /**
     * Apply a generated logo as the member's avatar/logo.
     * URL: /institution_hub_profile/apply_logo  (POST)
     */
    public function apply_logo() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $logoFile = basename(trim($this->_page_post_data['logo_file'] ?? ''));
        if (empty($logoFile) || !preg_match('/^[a-zA-Z0-9_\-.]+$/', $logoFile)) {
            return response()->json(['status' => 400, 'message' => 'Invalid logo file'], 400);
        }

        $filePath = public_path('upload/member_logo/' . $logoFile);
        if (!file_exists($filePath)) {
            return response()->json(['status' => 404, 'message' => 'Logo file not found'], 404);
        }

        $member_id = $this->_getProxyMemberId();
        DB::table('member')->where('id', $member_id)->update(['avatar' => $logoFile, 'updated_at' => now()]);
        DB::table('member_details')->where('member_id', $member_id)->update(['logo' => $logoFile, 'updated_at' => now()]);
        // Also store per-profile so admin-proxy profiles each keep their own logo
        if ($this->_institutionProfileHasColumn('logo')) {
            DB::table('institution_profiles')->where('member_id', $member_id)->update(['logo' => $logoFile, 'updated_at' => now()]);
        }

        return response()->json(['status' => 200, 'message' => 'Logo applied successfully']);
    }

    /**
     * Upload a gallery photo for the institution profile.
     * URL: /institution_hub_profile/upload_gallery  (POST, multipart)
     * max 8 photos per institution.
     */
    public function upload_gallery() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $file = \Illuminate\Support\Facades\Request::file('gallery_photo');
        if (empty($file) || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid file uploaded.'], 400);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return response()->json(['status' => 400, 'message' => 'Unsupported file type. Allowed: PNG, JPG, GIF, WEBP.'], 400);
        }

        if ($file->getSize() > 8 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'File too large. Maximum size is 8 MB.'], 400);
        }

        $member_id = $this->_getProxyMemberId();
        $hasGalleryJson = $this->_institutionProfileHasColumn('gallery_json');

        // Load current gallery (max 8 photos)
        $row = DB::table('institution_profiles')->where('member_id', $member_id)->first();
        $gallery = $this->_extractGalleryFromProfile($row ? (array)$row : [], $member_id);
        if (count($gallery) >= 8) {
            return response()->json(['status' => 400, 'message' => 'Maximum 8 photos allowed. Delete one first.'], 400);
        }

        $location = public_path('upload/inst_gallery');
        if (!file_exists($location)) {
            @mkdir($location, 0755, true);
        }

        $fileName = 'gallery_' . $member_id . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
        $savePath = $location . '/' . $fileName;

        if (!$file->move($location, $fileName)) {
            return response()->json(['status' => 500, 'message' => 'Failed to save uploaded file.'], 500);
        }

        // Resize to max 1400px wide for performance
        try {
            \Intervention\Image\Facades\Image::make($savePath)
                ->resize(1400, null, function ($c) {
                    $c->aspectRatio();
                    $c->upsize();
                })
                ->save($savePath, 88);
        } catch (\Throwable $e) {
            // Keep original if resize fails
        }

        $gallery[] = $fileName;
        $galleryJson = json_encode($gallery);

        if ($row) {
            $updateData = ['updated_at' => now()];
            if ($hasGalleryJson) {
                $updateData['gallery_json'] = $galleryJson;
            }
            DB::table('institution_profiles')->where('member_id', $member_id)->update($updateData);
        } else {
            $insertData = [
                'member_id'  => $member_id,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasGalleryJson) {
                $insertData['gallery_json'] = $galleryJson;
            }
            DB::table('institution_profiles')->insert($insertData);
        }

        if (!$hasGalleryJson) {
            $this->_saveGalleryFallback($member_id, $gallery);
        }

        return response()->json([
            'status'   => 200,
            'file'     => $fileName,
            'url'      => 'upload/inst_gallery/' . $fileName,
            'gallery'  => $gallery,
        ]);
    }

    /**
     * Delete a gallery photo from the institution profile.
     * URL: /institution_hub_profile/delete_gallery_photo  (POST)
     */
    public function delete_gallery_photo() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $fileName = basename(trim($this->_page_post_data['file'] ?? ''));
        // Validate filename is safe (alphanumeric, underscore, dash, dot only)
        if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\-.]+$/', $fileName)) {
            return response()->json(['status' => 400, 'message' => 'Invalid file name.'], 400);
        }

        $member_id = $this->_getProxyMemberId();
        $hasGalleryJson = $this->_institutionProfileHasColumn('gallery_json');
        $row = DB::table('institution_profiles')->where('member_id', $member_id)->first();
        if (!$row && empty($this->_loadGalleryFallback($member_id))) {
            return response()->json(['status' => 404, 'message' => 'Profile not found.'], 404);
        }

        $gallery = $this->_extractGalleryFromProfile($row ? (array)$row : [], $member_id);

        // Remove from gallery list
        $gallery = array_values(array_filter($gallery, fn($f) => basename($f) !== $fileName));

        // Delete file from disk
        $filePath = public_path('upload/inst_gallery/' . $fileName);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        if ($row) {
            $updateData = ['updated_at' => now()];
            if ($hasGalleryJson) {
                $updateData['gallery_json'] = json_encode($gallery);
            }
            DB::table('institution_profiles')->where('member_id', $member_id)->update($updateData);
        }

        if (!$hasGalleryJson) {
            $this->_saveGalleryFallback($member_id, $gallery);
        }

        return response()->json(['status' => 200, 'gallery' => $gallery]);
    }

    /**
     * Save the institution profile.
     * URL: /institution_hub_profile/save  (POST)
     */
    public function save() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $member_id = $this->_getProxyMemberId();
        $existing = DB::table('institution_profiles')->where('member_id', $member_id)->first();

        // ── Text blob fields (original) ─────────────────────────────────────────
        $textFields = ['website_url', 'institute_name', 'institution_category', 'summary', 'key_dates'];
        $data = [];
        foreach ($textFields as $f) {
            if ($this->_institutionProfileHasColumn($f)) {
                $data[$f] = isset($this->_page_post_data[$f]) ? trim($this->_page_post_data[$f]) : '';
            }
        }
        // Validate institution_category
        $validCategories = ['university', 'vocational', 'highschool', 'college', 'language_school',
            'primary_school', 'secondary_school', 'international_school', 'tutoring', 'other'];
        if (isset($data['institution_category']) && !in_array($data['institution_category'], $validCategories, true)) {
            $data['institution_category'] = 'university';
        }

        // ── New structured string fields ────────────────────────────────────────
        $structuredStringFields = ['city', 'address', 'phone', 'school_phases', 'annual_fees_range',
            'prospectus_url', 'student_teacher_ratio', 'academic_year', 'mission_statement',
            'description', 'banner_image', 'cost_of_living', 'registration_number',
            'intakes', 'visa_requirements'];
        foreach ($structuredStringFields as $f) {
            if ($this->_institutionProfileHasColumn($f) && array_key_exists($f, $this->_page_post_data)) {
                $data[$f] = trim((string)($this->_page_post_data[$f] ?? ''));
            }
        }

        // ── JSON array fields (sent as JSON strings from scrape output or textarea) ──
        $jsonArrayFields = ['curriculum', 'exam_boards', 'qualifications_awarded',
            'language_of_instruction', 'school_qualities', 'exam_results'];
        foreach ($jsonArrayFields as $f) {
            if (!$this->_institutionProfileHasColumn($f)) continue;
            // Accept either the pre-encoded JSON (from scrape result) or a raw comma-separated value
            $raw = $this->_page_post_data[$f] ?? null;
            if ($raw === null) continue;
            // If the front-end sent it as _json key (from scrape result fields)
            if (empty($raw)) { $data[$f] = '[]'; continue; }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data[$f] = json_encode(array_values(array_filter(array_map('strval', $decoded))), JSON_UNESCAPED_UNICODE);
            } else {
                // Fallback: treat as comma-separated
                $items = array_values(array_filter(array_map('trim', explode(',', $raw))));
                $data[$f] = json_encode($items, JSON_UNESCAPED_UNICODE);
            }
        }

        // ── Social links (JSON object) ──────────────────────────────────────────
        if ($this->_institutionProfileHasColumn('social_links') && array_key_exists('social_links', $this->_page_post_data)) {
            $rawSocial = $this->_page_post_data['social_links'] ?? '';
            if (empty($rawSocial)) {
                $data['social_links'] = '{}';
            } else {
                $decodedSocial = json_decode($rawSocial, true);
                if (is_array($decodedSocial)) {
                    $allowed = ['facebook','instagram','youtube','linkedin','twitter','wechat','bilibili'];
                    $clean = [];
                    foreach ($allowed as $k) {
                        if (!empty($decodedSocial[$k])) $clean[$k] = (string)$decodedSocial[$k];
                    }
                    $data['social_links'] = json_encode($clean, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        // ── Boolean tinyint fields ──────────────────────────────────────────────
        $boolFields = ['has_boarding', 'has_school_bus', 'has_scholarships',
            'has_chinese_language_support', 'has_extra_languages'];
        foreach ($boolFields as $f) {
            if (!$this->_institutionProfileHasColumn($f)) continue;
            if (!array_key_exists($f, $this->_page_post_data)) continue;
            $val = $this->_page_post_data[$f];
            if ($val === null || $val === '') {
                $data[$f] = null;
            } else {
                $data[$f] = ($val === '1' || $val === true || $val === 1) ? 1 : 0;
            }
        }

        // ── Compute profile_strength ────────────────────────────────────────────
        if ($this->_institutionProfileHasColumn('profile_strength')) {
            $strFields = [
                !empty($data['institute_name'] ?? $existing->institute_name ?? ''),
                !empty($data['website_url']    ?? $existing->website_url    ?? ''),
                !empty($data['summary']        ?? $existing->summary        ?? '') || !empty($data['description'] ?? $existing->description ?? ''),
                !empty($data['city']           ?? $existing->city           ?? '') || !empty($data['address'] ?? $existing->address ?? ''),
                !empty($data['phone']          ?? $existing->phone          ?? ''),
                !empty($data['annual_fees_range'] ?? $existing->annual_fees_range ?? ''),
                !empty($data['curriculum']     ?? $existing->curriculum     ?? ''),
                !empty($data['exam_boards']    ?? $existing->exam_boards    ?? ''),
                !empty($data['mission_statement'] ?? $existing->mission_statement ?? ''),
                !empty($data['school_qualities']  ?? $existing->school_qualities  ?? ''),
                !empty($existing->gallery_json ?? ''),
                !empty($this->_page_post_data['courses_json'] ?? ''),
                !empty($data['social_links']   ?? $existing->social_links   ?? ''),
                !empty($data['admission']      ?? $existing->admission      ?? ''),
                isset($data['has_boarding']) || isset($existing->has_boarding),
            ];
            $data['profile_strength'] = (int)round(array_sum(array_map('intval', $strFields)) / count($strFields) * 100);
        }

        $rawCourses = $this->_page_post_data['courses_json'] ?? '';
        $courses = $this->_decodeCoursesPayload($rawCourses);

        if ($this->_institutionProfileHasColumn('courses_json')) {
            $data['courses_json'] = !empty($courses) ? json_encode($courses) : '';
        } elseif ($this->_institutionProfileHasColumn('programs')) {
            $existingPrograms = ($existing && isset($existing->programs)) ? (string)$existing->programs : '';
            $existingWasFallbackJson = strpos($existingPrograms, self::PROGRAMS_COURSE_JSON_PREFIX) === 0;

            if (!empty($courses)) {
                $data['programs'] = $this->_encodeCoursesForProgramsFallback($courses);
            } elseif ($existingWasFallbackJson) {
                // If courses were previously saved via fallback format and now removed, clear that fallback payload.
                $data['programs'] = '';
            }
        }

        // Preserve existing gallery_json (gallery is managed via separate endpoints)
        if ($this->_institutionProfileHasColumn('gallery_json') && $existing && !empty($existing->gallery_json)) {
            $data['gallery_json'] = $existing->gallery_json;
        }
        $data['updated_at'] = now();

        if ($existing) {
            DB::table('institution_profiles')->where('member_id', $member_id)->update($data);
        } else {
            $data['member_id']  = $member_id;
            $data['status']     = 1;
            $data['created_at'] = now();
            DB::table('institution_profiles')->insert($data);
        }

        return response()->json(['status' => 200, 'message' => 'Profile saved successfully']);
    }

    /**
     * Generate AI-structured details for a single course (specific per-course data).
     * URL: /institution_hub_profile/generate_course  (POST)
     */
    public function generate_course() {
        if (empty($this->_current_member) || ((int)$this->_current_member['type'] !== 3 && !$this->_isAdmin())) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $institutionName = trim($this->_page_post_data['institution_name'] ?? '');
        $websiteUrl      = trim($this->_page_post_data['website_url'] ?? '');
        $courseName      = trim($this->_page_post_data['course_name'] ?? '');

        if (empty($courseName)) {
            return response()->json(['status' => 400, 'message' => 'Course name is required'], 400);
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            return response()->json(['status' => 500, 'message' => 'AI service not configured'], 500);
        }

        @set_time_limit(180);

        $apiBase = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/');
        $model   = env('XAI_MODEL', 'grok-4-1-fast-reasoning');

        $domain = '';
        if ($websiteUrl) {
            $parsed = parse_url($websiteUrl, PHP_URL_HOST);
            $domain = $parsed ?: preg_replace('#^https?://#i', '', $websiteUrl);
            $domain = rtrim($domain, '/');
        }
        // Determine qualification level from course name to tailor entry req guidance
        $isPostgrad = (bool) preg_match('/\b(master|mba|meng|msc|med|phd|doctor|graduate diploma|graduate certificate|postgraduate)\b/i', $courseName);
        $isVET      = (bool) preg_match('/\b(certificate|cert\b|diploma|advanced diploma|statement of attainment)\b/i', $courseName);
        $levelNote  = $isPostgrad ? 'POSTGRADUATE course — entry requires a bachelor degree or higher.'
                    : ($isVET    ? 'VET/VOCATIONAL course — entry may be open-entry or require Certificate III/IV.'
                                 : 'UNDERGRADUATE course — entry typically requires senior secondary (Year 12) results.');

        // Compose search queries that target authoritative pages
        $siteQ    = $domain ? "site:{$domain}" : '';
        $instQ    = $institutionName ? "\"{$institutionName}\"" : $siteQ;
        $courseQ  = "\"{$courseName}\"";
        $baseSearch = $instQ ?: $courseQ;

        $prompt = <<<PROMPT
You are a meticulous education data researcher with live web search access. Your job is to return VERIFIED, PRECISE data for ONE specific course — not generic institution-wide information.

COURSE: {$courseName}
INSTITUTION: {$institutionName}
WEBSITE: {$websiteUrl}
LEVEL CONTEXT: {$levelNote}

═══════════════════════════════════════════════════════════════
MANDATORY RESEARCH — you MUST execute ALL 7 searches below before writing any output. Do not skip any search.
═══════════════════════════════════════════════════════════════

Search 1 — OFFICIAL COURSE PAGE:
{$siteQ} {$courseQ}
Goal: Find the exact official course page. Read the full page: course structure, delivery mode, campus/es, intake months, duration, units overview, career outcomes.

Search 2 — CRICOS & QUALIFICATION CODE:
{$instQ} {$courseQ} CRICOS course code
ALSO search: cricos.teqsa.gov.au {$courseQ} {$instQ}
Goal: Find the CRICOS course code (format: numbers + one letter, e.g. 012345A or 103484G) for this specific course, NOT the provider code. Also find national qualification code (e.g. BSB50120) for VET courses.

Search 3 — ENGLISH ENTRY REQUIREMENTS (course-specific):
{$instQ} {$courseQ} "IELTS" OR "PTE" OR "TOEFL" international English requirements
Goal: Find the EXACT English proficiency minimums for THIS specific course or faculty/school. Many institutions have different IELTS requirements per course — return the one for THIS course. If only a faculty/university-wide requirement is listed and no course-specific one exists, use the faculty/school level minimum.

Search 4 — ACADEMIC ENTRY REQUIREMENTS:
{$instQ} {$courseQ} entry requirements admission criteria international students
Goal: Find minimum academic qualification required: ATAR cutoff for UG, GPA/WAM for PG, or open-entry/Certificate III for VET. Include any prerequisite subjects.

Search 5 — TUITION FEES (year-specific):
{$instQ} {$courseQ} international student tuition fee 2025 2026 annual
ALSO check: {$siteQ} fees tuition schedule international
Goal: Find the ANNUAL tuition fee in local currency for THIS specific course for international students. State the year (e.g. AUD 38,400 per year (2026)). If per-credit pricing, calculate annual (e.g. 24 credit points × AUD 1,600 = AUD 38,400).

Search 6 — OSHC, LIVING COSTS & FEE NOTES:
{$instQ} OSHC health cover international students OR "living costs" site:homeaffairs.gov.au
Goal: Find OSHC health insurance estimate for single student per year. Find estimated living costs from Home Affairs or the institution. Find application fee / enrolment fee.

Search 7 — SCHOLARSHIPS:
{$instQ} scholarships international students {$courseQ} OR {$instQ} scholarships international 2025 2026
Goal: Find scholarships specifically available to international students for this course or field of study.

═══════════════════════════════════════════════════════════════
ACCURACY RULES — strictly follow these:
═══════════════════════════════════════════════════════════════
1. Return data SPECIFIC to this course. If fees/IELTS vary per course, use THIS course's values only.
2. For IELTS, PTE, TOEFL, Cambridge, Duolingo — report the equivalent minimums for THIS course. If the institution only lists IELTS, search explicitly for PTE/TOEFL equivalencies on their English requirements page before leaving those fields empty.
3. CRICOS codes are COURSE-specific in Australia. Provider code ≠ course code. Verify it is a course CRICOS code.
4. All fees must include the academic year (e.g. "(2026)"). Never omit the year.
5. For req_academic: for UG → state ATAR/rank, Year 12 equivalent, prerequisite subjects. For PG → state minimum bachelor degree classification (e.g. "2:1 Honours or GPA 5.5/7.0"). For VET → state minimum entry (e.g. "Open entry; no formal prerequisites required" or "Year 10 or equivalent preferred" or "Certificate III in related field"). NEVER leave req_academic empty for VET — if no specific requirement is published, use "Open entry; no formal academic prerequisites required".
6. NEVER leave overview empty. Always write a complete description even if web search is limited.
7. For entry (intake months): if specific dates are not published, write "Rolling intake — contact institution" rather than leaving blank.
8. For req_documents: always list at minimum "- Passport copy\n- Completed application form\n- Proof of English proficiency (if applicable)" even if not explicitly stated on the website.
9. If a field genuinely cannot be verified from any source (except overridden by rules 5, 7, 8 above), use empty string "".

═══════════════════════════════════════════════════════════════
OUTPUT — return ONLY a single valid JSON object, no code fences, no text outside JSON:
═══════════════════════════════════════════════════════════════
{
  "code": "National qualification code (VET: e.g. SIT50422 | UG/PG: official course code from institution, e.g. ACOM2001 or empty if none). Empty string if not applicable.",
  "cricos": "CRICOS COURSE code — alphanumeric ending in a letter (e.g. 103484G, 0101979A). NOT the provider code. Empty for non-Australian or non-CRICOS courses.",
  "delivery": "Delivery mode and all campuses (e.g. \"On-campus — Darwin; Online available\" or \"On-campus — City Campus, Online\"). Keep concise.",
  "duration": "Standard full-time duration (e.g. \"3 years full-time\", \"18 months full-time\", \"52 weeks\").",
  "entry": "Intake months (e.g. \"February, July\" or \"February\" or \"Rolling — any Monday\"). If not explicitly published, write \"Rolling intake — contact institution\".",
  "overview": "200-280 words. Plain text only, no bullets. Cover: what the course is, key study areas and units, any specialisation streams or majors, practical experience components, career outcomes and industries, and what makes this course distinctive at this institution. Do NOT mention fees or entry requirements.",
  "req_academic": "Minimum academic entry specific to this course. UG example: \"Year 12 or equivalent, ATAR 72+ (2026); prerequisite: Mathematics Methods\". PG example: \"Bachelor degree in Engineering (min. GPA 5.0/7.0 or 2:1 equivalent)\". VET example: \"Open entry; Year 10 or equivalent preferred\". For VET courses, NEVER leave blank — use 'Open entry; no formal academic prerequisites required' if nothing specific is published.",
  "req_ielts": "IELTS Academic minimum for this course (e.g. \"6.5 overall, no individual band below 6.0\"). Include sub-band requirements. Empty string if IELTS not accepted or not required.",
  "req_pte": "PTE Academic minimum (e.g. \"58, no communicative skill below 50\"). Include sub-score requirements. Empty string if not accepted.",
  "req_toefl": "TOEFL iBT minimum (e.g. \"79, writing 21, reading 13\"). Include sub-score requirements. Empty string if not accepted.",
  "req_cambridge": "Cambridge C1 Advanced or C2 Proficiency minimum (e.g. \"176 overall, no skill below 162\"). Empty string if not accepted.",
  "req_duolingo": "Duolingo English Test minimum (e.g. \"105, no subscore below 90\"). Empty string if not accepted.",
  "req_documents": "Required application documents, one per line starting with '- '. Always include at minimum: passport copy, completed application form, evidence of English proficiency (if required). Add any additional documents listed on the admissions page. Example:\n- Passport copy\n- Completed application form\n- Certified academic transcripts\n- English proficiency test results (if required)\n- Personal statement / Statement of purpose",
  "req_notes": "Work experience requirements, portfolio, interview, licensing, RPL, age restrictions, Working With Children Check, professional registration, or other notable entry conditions. Empty string if none.",
  "fee_tuition": "Annual international tuition for THIS course with year stated (e.g. \"AUD 38,400 per year (2026)\"). If semester-based: \"AUD 19,200 per semester = AUD 38,400/year (2026)\". Never omit year.",
  "fee_application": "Application or enrolment fee (e.g. \"None\" or \"AUD 100 non-refundable application fee\"). State the currency.",
  "fee_oshc": "Estimated OSHC health cover cost (e.g. \"~AUD 720/year single cover (2026 Medibank rate)\"). Empty string for non-Australian institutions.",
  "fee_living": "Estimated annual living cost (e.g. \"AUD 29,710/year (Australian Government, Department of Home Affairs estimate 2024)\"). Cite the source.",
  "fee_notes": "Payment schedule, instalment terms, refund policy, or bond/deposit requirements. Empty string if none.",
  "scholarships": "Scholarships for international students relevant to this course. Format each as: '- [Scholarship name]: [value or % of fees], [eligibility / ATAR or GPA requirement]'. Empty string if genuinely none found."
}
PROMPT;

        $ch = curl_init("{$apiBase}/v1/responses");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'             => $model,
                'input'             => [[
                    'role'    => 'user',
                    'content' => [['type' => 'input_text', 'text' => $prompt]],
                ]],
                'temperature'       => 0,
                'max_output_tokens' => 8192,
                'tools'             => [['type' => 'web_search']],
                'tool_choice'       => 'required',
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 130,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || $code !== 200) {
            \Log::error('generate_course xAI failed', ['code' => $code, 'err' => $err]);
            return response()->json(['status' => 500, 'message' => 'AI generation failed. Please try again.'], 500);
        }

        // Extract output text
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

        $clean = trim($raw);
        $clean = preg_replace('/^```json?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/i', '', $clean);
        $clean = preg_replace('/<grok:[^>]+>.*?<\/grok:[^>]+>/s', '', $clean);
        $clean = preg_replace('/<grok:[^>]+\/>/s', '', $clean);

        $data = json_decode($clean, true);
        if (!is_array($data) && preg_match('/\{[\s\S]+\}/u', $clean, $m)) {
            $data = json_decode($m[0], true);
        }
        if (!is_array($data)) {
            // Last-resort: repair literal newlines inside string values
            $repaired = preg_replace_callback(
                '/"((?:[^"\\\\]|\\\\.)*)"/s',
                function ($match) { return '"' . str_replace(["\r\n","\r","\n"], '\\n', $match[1]) . '"'; },
                $clean
            );
            $data = json_decode($repaired, true);
        }
        if (!is_array($data)) {
            \Log::warning('generate_course: JSON parse failed', ['raw' => substr($raw, 0, 500)]);
            return response()->json(['status' => 500, 'message' => 'AI returned invalid data format. Please try again.'], 500);
        }

        $fields = ['code','cricos','delivery','duration','entry','overview',
                   'req_academic','req_ielts','req_pte','req_toefl','req_cambridge','req_duolingo','req_documents','req_notes',
                   'fee_tuition','fee_application','fee_oshc','fee_living','fee_notes','scholarships'];
        $result = [];
        foreach ($fields as $f) {
            $result[$f] = isset($data[$f]) ? (string)$data[$f] : '';
        }

        return response()->json(['status' => 200, 'data' => $result]);
    }
}


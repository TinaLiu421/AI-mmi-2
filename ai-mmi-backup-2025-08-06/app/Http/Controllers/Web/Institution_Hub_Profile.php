<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;

class Institution_Hub_Profile extends WebController {

    /**
     * Show the institution profile setup / edit page.
     * URL: /institution_hub_profile  (GET)
     */
    public function index() {
        // Must be logged in as an education institution
        if (empty($this->_current_member) || (int)$this->_current_member['type'] !== 3) {
            header('Location: ' . $this->toURL('account_login'));
            exit();
        }

        $member_id = (int)$this->_current_member['id'];

        // Fetch institution_type from member details
        $details = DB::table('member_details')->where('member_id', $member_id)->first();
        $institution_type = $details ? (int)$details->institution_type : 1;

        if ($institution_type !== 2) {
            // Not an education institution — redirect to account
            header('Location: ' . $this->toURL('account'));
            exit();
        }

        // Load existing profile if any
        $profile = DB::table('institution_profiles')->where('member_id', $member_id)->first();

        return $this->pageData([
            'profile'          => $profile ? (array)$profile : [],
            'member'           => $this->_current_member,
            'institution_type' => $institution_type,
        ])->pageView();
    }

    /**
     * Scrape a website URL using xAI and return structured profile sections.
     * URL: /institution_hub_profile/scrape  (POST)
     */
    public function scrape() {
        if (empty($this->_current_member) || (int)$this->_current_member['type'] !== 3) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $url = trim($this->_page_post_data['website_url'] ?? '');
        if (empty($url)) {
            return response()->json(['status' => 400, 'message' => 'Website URL is required'], 400);
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'Invalid URL format'], 400);
        }

        $apiKey = env('XAI_API_KEY');
        if (!$apiKey) {
            return response()->json(['status' => 500, 'message' => 'AI service not configured'], 500);
        }

        $apiBase = rtrim(env('XAI_API_BASE', 'https://api.x.ai'), '/');
        $model   = env('XAI_MODEL', 'grok-4-1-fast-reasoning');

        $prompt = "You are an expert education institution researcher. A student matching platform needs a COMPLETE, HIGHLY DETAILED profile of this institution.\n\n"
            . "Visit and thoroughly analyse this institution website: {$url}\n\n"
            . "Return ONLY a valid JSON object with exactly 6 keys: institute_name, programs, admission, fees, summary, key_dates.\n\n"
            . "CRITICAL FORMATTING RULES — every field except institute_name must use this plain-text structure so it renders cleanly in a textarea:\n"
            . "- Use ALL-CAPS headings followed by a colon and newline for each section (e.g. UNDERGRADUATE:\\n)\n"
            . "- List each item on its own line starting with '- ' (dash space)\n"
            . "- Separate major sections with a blank line (\\n\\n)\n"
            . "- Do NOT use commas to separate list items — each item gets its own line\n"
            . "- Do NOT use markdown (no **, no ##, no *)\n\n"
            . "\"institute_name\": Full official name only (e.g. \"The University of Sydney\").\n\n"
            . "\"programs\": List ALL programs grouped by faculty. Format:\n"
            . "FACULTY NAME:\n- Degree 1\n- Degree 2\n\nNEXT FACULTY:\n- Degree 1\n...\n\n"
            . "Cover every faculty/school. Include Bachelor, Master, PhD, Diploma, Certificate levels.\n\n"
            . "\"admission\": Format:\n"
            . "UNDERGRADUATE REQUIREMENTS:\n- Domestic: ...\n- International: ...\n\nPOSTGRADUATE REQUIREMENTS:\n- Minimum GPA: ...\n- Relevant degree: ...\n\nENGLISH LANGUAGE:\n- IELTS (UG): ...\n- IELTS (PG): ...\n- TOEFL (UG): ...\n- PTE (UG): ...\n\nAPPLICATION PROCESS:\n- Step 1: ...\n- Step 2: ...\n\nINTERNATIONAL STUDENTS:\n- Visa: ...\n- OSHC: ...\n\nENTRANCE EXAMS / INTERVIEWS:\n- ...\n\nPATHWAY PROGRAMS:\n- ...\n\n"
            . "\"fees\": Format:\n"
            . "DOMESTIC UNDERGRADUATE (per year):\n- Arts/Social Sciences: AUD ...\n- Engineering: AUD ...\n- Medicine: AUD ...\n\nINTERNATIONAL UNDERGRADUATE (per year):\n- Arts/Social Sciences: AUD ...\n- Engineering: AUD ...\n\nDOMESTIC POSTGRADUATE (per year):\n- ...\n\nINTERNATIONAL POSTGRADUATE (per year):\n- ...\n\nOTHER FEES:\n- Student Services & Amenities Fee (SSAF): AUD ...\n\nLIVING COSTS (estimated per year):\n- Accommodation: AUD ...\n- Food: AUD ...\n- Transport: AUD ...\n- Total estimate: AUD ...\n\nSCHOLARSHIPS:\n- [Name]: [amount], [eligibility]\n- ...\n\nPAYMENT OPTIONS:\n- ...\n\n"
            . "\"summary\": A flowing paragraph overview (min 150 words). Cover: founding year, location, campuses, total enrolment, student-to-staff ratio, world rankings (QS/THE/ARWU), research strengths, facilities, student support, industry partnerships, graduate outcomes, notable alumni, mission, unique strengths.\n\n"
            . "\"key_dates\": Format:\n"
            . "SEMESTER 1 (February/March intake):\n- Application opens: ...\n- Domestic deadline: ...\n- International deadline: ...\n- Offer release: ...\n- Enrolment: ...\n- Orientation: ...\n- Classes begin: ...\n- Census date: ...\n- Last withdrawal date: ...\n- Exam period: ...\n- Results release: ...\n\nSEMESTER 2 (July/August intake):\n- Application opens: ...\n[same structure]\n\nOPEN DAYS:\n- ...\n\nSCHOLARSHIP DEADLINES:\n- ...\n\n"
            . "Return ONLY the raw JSON object. No markdown, no code fences. Use \\n literally in JSON string values for line breaks. If data is unavailable, write '(check website)' on that line.";

        $payload = [
            'model'       => $model,
            'input'       => [
                [
                    'role'    => 'user',
                    'content' => [[ 'type' => 'input_text', 'text' => $prompt ]],
                ]
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init("{$apiBase}/v1/responses");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            \Log::error('Institution profile scrape xAI error', ['code' => $httpCode, 'error' => $curlError, 'body' => $responseBody]);
            return response()->json(['status' => 500, 'message' => 'AI extraction failed. Please try again.'], 500);
        }

        $decoded = json_decode($responseBody, true);

        // xAI /v1/responses returns output[*].content[*].text
        $rawText = '';
        if (!empty($decoded['output'])) {
            foreach ($decoded['output'] as $out) {
                if (!empty($out['content'])) {
                    foreach ($out['content'] as $c) {
                        if (($c['type'] ?? '') === 'output_text') {
                            $rawText = $c['text'] ?? '';
                            break 2;
                        }
                    }
                }
            }
        }

        // Try to parse JSON from the response text
        $extracted = [];
        if (!empty($rawText)) {
            // Strip markdown code fences if present
            $cleanJson = preg_replace('/^```json?\s*/i', '', trim($rawText));
            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
            $extracted = json_decode($cleanJson, true);
        }

        $fields = ['institute_name', 'programs', 'admission', 'fees', 'summary', 'key_dates'];
        if (!is_array($extracted)) {
            $extracted = [];
        }
        $result = [];
        foreach ($fields as $f) {
            $result[$f] = isset($extracted[$f]) ? (string)$extracted[$f] : '';
        }
        $result['website_url'] = $url;

        return response()->json(['status' => 200, 'data' => $result]);
    }

    /**
     * Fetch the real institution logo from their website.
     * Tries: Clearbit → Google favicon (sz=256) → apple-touch-icon → common logo paths.
     * NOTE: og:image is intentionally excluded — it is nearly always a campus/people photo.
     * URL: /institution_hub_profile/generate_logo  (POST)
     */
    public function generate_logo() {
        if (empty($this->_current_member) || (int)$this->_current_member['type'] !== 3) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $websiteUrl = trim($this->_page_post_data['website_url'] ?? '');
        if (empty($websiteUrl) || !filter_var($websiteUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'A valid website URL is required to fetch the logo.'], 400);
        }

        $member_id = (int)$this->_current_member['id'];
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
        $fetch = function($url, $timeout = 15) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; LogoFetcher/1.0)',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ct   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            return [$body, $code, $ct];
        };

        $isLogoImage = function($body, $ct, $minBytes = 500) {
            if (empty($body) || strlen($body) < $minBytes) return false;
            if (strpos($ct, 'image') !== false) return true;
            // Detect by magic bytes
            if (substr($body, 0, 4) === "\x89PNG") return true;
            if (substr($body, 0, 3) === "\xFF\xD8\xFF") return true; // JPEG
            if (substr($body, 0, 6) === 'GIF87a' || substr($body, 0, 6) === 'GIF89a') return true;
            if (substr($body, 0, 4) === 'RIFF' && substr($body, 8, 4) === 'WEBP') return true;
            if (strpos($body, '<svg') !== false) return true;
            return false;
        };

        // --- Method 1: Clearbit Logo API (best quality branded logo) ---
        if (!empty($domain)) {
            [$body, $code, $ct] = $fetch("https://logo.clearbit.com/{$domain}?size=400&format=png");
            if ($code === 200 && $isLogoImage($body, $ct, 1000)) {
                $imageData   = $body;
                $contentType = 'image/png';
            }
        }

        // --- Method 2: Scrape homepage — look for apple-touch-icon ONLY (never og:image) ---
        if (empty($imageData)) {
            [$html] = $fetch($websiteUrl, 20);
            if (!empty($html)) {
                $logoUrl = null;

                // apple-touch-icon (brand icon, always square, suitable as logo)
                if (preg_match('/<link[^>]+rel=["\']apple-touch-icon(?:-precomposed)?["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
                    $logoUrl = $m[1];
                }
                if (!$logoUrl && preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']apple-touch-icon(?:-precomposed)?["\'][^>]*>/i', $html, $m)) {
                    $logoUrl = $m[1];
                }

                // Largest icon in <link rel="icon"> with sizes attribute (e.g. 192x192)
                if (!$logoUrl) {
                    preg_match_all('/<link[^>]+rel=["\']icon["\'][^>]+sizes=["\'](\d+)x\d+["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER);
                    if (!$logoUrl) {
                        preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]+sizes=["\'](\d+)x\d+["\'][^>]+rel=["\']icon["\'][^>]*>/i', $html, $matches2, PREG_SET_ORDER);
                        if (!empty($matches2)) {
                            usort($matches2, function($a, $b) { return (int)$b[2] - (int)$a[2]; });
                            $logoUrl = $matches2[0][1];
                        }
                    } else {
                        usort($matches, function($a, $b) { return (int)$b[1] - (int)$a[1]; });
                        $logoUrl = $matches[0][2];
                    }
                }

                // <img> tag whose src/class/id/alt contains "logo" (skip staff/hero photos)
                if (!$logoUrl) {
                    if (preg_match('/<img[^>]+(?:id|class|alt|src)=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+\.(png|svg|jpg|gif|webp))["\'][^>]*>/i', $html, $m)) {
                        $logoUrl = $m[1];
                    }
                    if (!$logoUrl && preg_match('/<img[^>]+src=["\']([^"\']+\.(png|svg|jpg|gif|webp))["\'][^>]+(?:id|class|alt)=["\'][^"\']*logo[^"\']*["\'][^>]*>/i', $html, $m)) {
                        $logoUrl = $m[1];
                    }
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
                    if ($code === 200 && $isLogoImage($body, $ct, 200)) {
                        $imageData   = $body;
                        $contentType = $ct;
                    }
                }
            }
        }

        // --- Method 3: Google high-res favicon (sz=256, reliable brand icon) ---
        if (empty($imageData) && !empty($domain)) {
            [$body, $code, $ct] = $fetch("https://www.google.com/s2/favicons?domain={$domain}&sz=256");
            if ($code === 200 && $isLogoImage($body, $ct, 100)) {
                $imageData   = $body;
                $contentType = $ct ?: 'image/png';
            }
        }

        // --- Method 4: Probe common logo file paths ---
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
                '/logo.png',
                '/logo.svg',
            ];
            foreach ($paths as $path) {
                [$body, $code, $ct] = $fetch($baseUrl . $path, 8);
                if ($code === 200 && $isLogoImage($body, $ct, 200)) {
                    $imageData   = $body;
                    $contentType = $ct;
                    break;
                }
            }
        }

        if (empty($imageData)) {
            return response()->json(['status' => 500, 'message' => 'Could not retrieve logo from the institution website. Please upload a logo manually.'], 500);
        }

        // Determine extension
        $ext = 'png';
        if (strpos($contentType, 'svg') !== false || strpos($imageData, '<svg') !== false) $ext = 'svg';
        elseif (strpos($contentType, 'jpeg') !== false || strpos($contentType, 'jpg') !== false) $ext = 'jpg';
        elseif (strpos($contentType, 'gif') !== false) $ext = 'gif';
        elseif (strpos($contentType, 'webp') !== false) $ext = 'webp';
        // Detect from bytes
        if ($ext === 'png' && substr($imageData, 0, 3) === "\xFF\xD8\xFF") $ext = 'jpg';
        if ($ext === 'png' && (substr($imageData, 0, 6) === 'GIF87a' || substr($imageData, 0, 6) === 'GIF89a')) $ext = 'gif';

        $fileName = 'logo_' . $member_id . '_' . time() . '.' . $ext;
        $savePath = $location . '/' . $fileName;

        if (!file_put_contents($savePath, $imageData)) {
            return response()->json(['status' => 500, 'message' => 'Failed to save logo.'], 500);
        }

        // Resize to 400x400 (skip SVG)
        if ($ext !== 'svg') {
            try {
                \Intervention\Image\Facades\Image::make($savePath)
                    ->fit(400, 400)
                    ->save($savePath);
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
        if (empty($this->_current_member) || (int)$this->_current_member['type'] !== 3) {
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

        $member_id = (int)$this->_current_member['id'];
        DB::table('member')->where('id', $member_id)->update(['avatar' => $logoFile, 'updated_at' => now()]);
        DB::table('member_details')->where('member_id', $member_id)->update(['logo' => $logoFile, 'updated_at' => now()]);

        return response()->json(['status' => 200, 'message' => 'Logo applied successfully']);
    }

    /**
     * Save the institution profile.
     * URL: /institution_hub_profile/save  (POST)
     */
    public function save() {
        if (empty($this->_current_member) || (int)$this->_current_member['type'] !== 3) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
        }

        $member_id = (int)$this->_current_member['id'];

        $fields = ['website_url', 'institute_name', 'programs', 'admission', 'fees', 'summary', 'key_dates'];
        $data = [];
        foreach ($fields as $f) {
            $data[$f] = isset($this->_page_post_data[$f]) ? trim($this->_page_post_data[$f]) : '';
        }
        $data['updated_at'] = now();

        $exists = DB::table('institution_profiles')->where('member_id', $member_id)->exists();
        if ($exists) {
            DB::table('institution_profiles')->where('member_id', $member_id)->update($data);
        } else {
            $data['member_id']  = $member_id;
            $data['status']     = 1;
            $data['created_at'] = now();
            DB::table('institution_profiles')->insert($data);
        }

        return response()->json(['status' => 200, 'message' => 'Profile saved successfully']);
    }
}

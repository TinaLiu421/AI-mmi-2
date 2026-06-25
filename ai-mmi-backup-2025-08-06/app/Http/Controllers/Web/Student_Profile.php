<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Student_Profile — lets type=1 (individual/student) members fill & view their academic profile.
 *
 * Routes (auto via RouteMapping):
 *   GET  /{lang}/student_profile           → index()   — edit own profile
 *   POST /{lang}/student_profile/save      → save()    — AJAX save a section
 */
class Student_Profile extends WebController
{
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        // Guests are allowed to reach this page; index() handles the guest state
    }

    // -------------------------------------------------------
    // GET /{lang}/student_profile — view/edit own profile
    // -------------------------------------------------------
    public function index()
    {
        // Guest: show login prompt within the page layout
        if (empty($this->_current_member)) {
            return $this->pageData(['is_guest' => true])->pageView('student_profile');
        }

        $member = $this->_current_member;

        // Only type=1 (individual) can have a student academic profile
        if ((int)($member['type'] ?? 0) !== 1) {
            $this->doRedirect($this->toURL('home'));
            return;
        }

        $memberId = (int)$member['id'];

        $profile = DB::table('student_academic_profiles')
            ->where('member_id', $memberId)
            ->first();

        // Profile completeness (0–100)
        $completeness = $this->_calcCompleteness($profile);

        return $this->pageData([
            'profile'      => $profile,
            'completeness' => $completeness,
        ])->pageView('student_profile');
    }

    // -------------------------------------------------------
    // POST /{lang}/student_profile/save — AJAX section save
    // -------------------------------------------------------
    public function save()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }

        $memberId = (int)$member['id'];
        $section  = (string)request()->input('section', '');
        $data     = (array)request()->input('data', []);

        $updateData = $this->_buildUpdateData($section, $data);
        if ($updateData === null) {
            return response()->json(['status' => 400, 'message' => 'Unknown section']);
        }

        $updateData['updated_by'] = $memberId;
        $updateData['updated_at'] = now()->toDateTimeString();

        $exists = DB::table('student_academic_profiles')
            ->where('member_id', $memberId)
            ->exists();

        try {
            if ($exists) {
                DB::table('student_academic_profiles')
                    ->where('member_id', $memberId)
                    ->update($updateData);
            } else {
                $updateData['member_id']  = $memberId;
                $updateData['created_by'] = $memberId;
                $updateData['created_at'] = now()->toDateTimeString();
                $updateData['profile_views'] = 0;
                $updateData['status']     = 1;
                DB::table('student_academic_profiles')->insert($updateData);
            }
        } catch (\Exception $e) {
            Log::error('Student_Profile::save error: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Save failed, please try again.']);
        }

        // Return updated completeness
        $profile      = DB::table('student_academic_profiles')->where('member_id', $memberId)->first();
        $completeness = $this->_calcCompleteness($profile);

        return response()->json([
            'status'       => 200,
            'message'      => 'Saved successfully',
            'completeness' => $completeness,
        ]);
    }

    // -------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------

    private function _buildUpdateData(string $section, array $data): ?array
    {
        switch ($section) {
            case 'hero':
                return [
                    'headline'        => mb_substr(strip_tags($data['headline'] ?? ''), 0, 300),
                    'nationality'     => mb_substr(strip_tags($data['nationality'] ?? ''), 0, 100),
                    'current_country' => mb_substr(strip_tags($data['current_country'] ?? ''), 0, 100),
                ];

            case 'bio':
                return [
                    'bio' => mb_substr(strip_tags($data['bio'] ?? ''), 0, 2000),
                ];

            case 'target':
                $fields = array_slice(array_map(
                    fn($f) => mb_substr(strip_tags((string)$f), 0, 100),
                    (array)($data['target_fields'] ?? [])
                ), 0, 10);
                return [
                    'target_degree'      => mb_substr(strip_tags($data['target_degree'] ?? ''), 0, 50),
                    'target_fields'      => json_encode(array_values(array_filter($fields))),
                    'target_intake_year' => mb_substr(strip_tags($data['target_intake_year'] ?? ''), 0, 50),
                    'budget_usd_min'     => ($v = (int)($data['budget_usd_min'] ?? 0)) > 0 ? $v : null,
                    'budget_usd_max'     => ($v = (int)($data['budget_usd_max'] ?? 0)) > 0 ? $v : null,
                ];

            case 'education':
                $entries = array_slice((array)($data['entries'] ?? []), 0, 10);
                $clean = array_map(fn($e) => [
                    'degree'      => mb_substr(strip_tags($e['degree'] ?? ''), 0, 100),
                    'institution' => mb_substr(strip_tags($e['institution'] ?? ''), 0, 200),
                    'field'       => mb_substr(strip_tags($e['field'] ?? ''), 0, 200),
                    'country'     => mb_substr(strip_tags($e['country'] ?? ''), 0, 100),
                    'gpa'         => mb_substr(strip_tags($e['gpa'] ?? ''), 0, 20),
                    'grad_year'   => mb_substr(strip_tags($e['grad_year'] ?? ''), 0, 10),
                    'logo_url'    => $this->_sanitizeLogoUrl($e['logo_url'] ?? ''),
                ], $entries);
                return ['education_history' => json_encode(array_values($clean))];

            case 'language':
                $entries = array_slice((array)($data['entries'] ?? []), 0, 10);
                $clean = array_map(fn($e) => [
                    'test'  => mb_substr(strip_tags($e['test'] ?? ''), 0, 50),
                    'score' => mb_substr(strip_tags($e['score'] ?? ''), 0, 20),
                    'date'  => mb_substr(strip_tags($e['date'] ?? ''), 0, 20),
                ], $entries);
                return ['language_scores' => json_encode(array_values($clean))];

            case 'achievements':
                $entries = array_slice((array)($data['entries'] ?? []), 0, 20);
                $clean = array_map(fn($e) => [
                    'title'       => mb_substr(strip_tags($e['title'] ?? ''), 0, 200),
                    'issuer'      => mb_substr(strip_tags($e['issuer'] ?? ''), 0, 200),
                    'year'        => mb_substr(strip_tags($e['year'] ?? ''), 0, 10),
                    'description' => mb_substr(strip_tags($e['description'] ?? ''), 0, 500),
                ], $entries);
                return ['achievements' => json_encode(array_values($clean))];

            case 'work':
                $entries = array_slice((array)($data['entries'] ?? []), 0, 10);
                $clean = array_map(fn($e) => [
                    'title'       => mb_substr(strip_tags($e['title'] ?? ''), 0, 200),
                    'company'     => mb_substr(strip_tags($e['company'] ?? ''), 0, 200),
                    'country'     => mb_substr(strip_tags($e['country'] ?? ''), 0, 100),
                    'from'        => mb_substr(strip_tags($e['from'] ?? ''), 0, 20),
                    'to'          => mb_substr(strip_tags($e['to'] ?? ''), 0, 20),
                    'current'     => (bool)($e['current'] ?? false),
                    'description' => mb_substr(strip_tags($e['description'] ?? ''), 0, 500),
                    'logo_url'    => $this->_sanitizeLogoUrl($e['logo_url'] ?? ''),
                ], $entries);
                return ['work_experience' => json_encode(array_values($clean))];
        }

        return null;
    }

    // -------------------------------------------------------
    // POST /{lang}/student_profile/upload_avatar
    // -------------------------------------------------------
    public function upload_avatar()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }
        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }
        $file = request()->file('avatar');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid file provided']);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            return response()->json(['status' => 400, 'message' => 'Invalid file type. Use JPG, PNG or WEBP.']);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'File too large. Max 5MB.']);
        }
        $fileName = md5(uniqid((string)rand())) . '.' . $ext;
        $location = 'upload/member_avatar';
        if (!file_exists(public_path($location))) {
            @mkdir(public_path($location), 0755, true);
        }
        if (!$file->move(public_path($location), $fileName)) {
            return response()->json(['status' => 500, 'message' => 'Upload failed. Please try again.']);
        }
        try {
            \Intervention\Image\Facades\Image::make(public_path($location.'/'.$fileName))
                ->fit(400, 400)->save(public_path($location.'/'.$fileName));
        } catch (\Exception $e) { /* keep original on resize failure */ }
        $this->_member_model->renewAvatar((int)$member['id'], $fileName);
        return response()->json([
            'status'     => 200,
            'message'    => 'Photo updated',
            'avatar_url' => 'upload/member_avatar/' . $fileName,
        ]);
    }

    // -------------------------------------------------------
    // POST /{lang}/student_profile/upload_cover
    // -------------------------------------------------------
    public function upload_cover()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }
        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }
        $file = request()->file('cover');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid file provided']);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            return response()->json(['status' => 400, 'message' => 'Invalid file type. Use JPG, PNG or WEBP.']);
        }
        if ($file->getSize() > 8 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'File too large. Max 8MB.']);
        }
        $fileName = md5(uniqid((string)rand())) . '.' . $ext;
        $location = 'upload/member_coverphoto';
        if (!file_exists(public_path($location))) {
            @mkdir(public_path($location), 0755, true);
        }
        if (!$file->move(public_path($location), $fileName)) {
            return response()->json(['status' => 500, 'message' => 'Upload failed. Please try again.']);
        }
        try {
            \Intervention\Image\Facades\Image::make(public_path($location.'/'.$fileName))
                ->resize(1200, 300, function($c){$c->aspectRatio();$c->upsize();})
                ->save(public_path($location.'/'.$fileName));
        } catch (\Exception $e) { /* keep original */ }
        $this->_member_model->renewAlias((int)$member['id'], ['coverphoto' => $fileName]);
        return response()->json([
            'status'    => 200,
            'message'   => 'Cover updated',
            'cover_url' => 'upload/member_coverphoto/' . $fileName,
        ]);
    }

    // -------------------------------------------------------
    // POST /{lang}/student_profile/fetch_logos
    // -------------------------------------------------------
    public function fetch_logos()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }
        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }
        $memberId = (int)$member['id'];
        $type    = (string)request()->input('type', '');
        $entries = (array)request()->input('entries', []);
        if (!in_array($type, ['education','work']) || empty($entries)) {
            return response()->json(['status' => 400, 'message' => 'Invalid input']);
        }
        $entries = array_slice($entries, 0, 10);

        $namesList = [];
        foreach ($entries as $i => $entry) {
            // education uses 'institution', work uses 'company'
            $name    = trim(strip_tags(
                $type === 'work'
                    ? ($entry['company'] ?? $entry['name'] ?? '')
                    : ($entry['institution'] ?? $entry['name'] ?? '')
            ));
            $country = trim(strip_tags($entry['country'] ?? ''));
            if (!empty($name)) {
                $namesList[$i] = ($i + 1) . '. ' . $name . ($country ? " ($country)" : '');
            }
        }

        $logos = array_fill(0, count($entries), '');

        // Blocked/parked domain patterns — reject these from Clearbit results
        $parkedDomainPattern = '/godaddy|dan\.com|sedo\.|afternic|buydomains|namecheap|hugedomains|brandable|domainagent|undeveloped\.com/i';

        if (!empty($namesList)) {
            foreach ($namesList as $idx => $label) {
                // strip "N. " prefix and country in parentheses
                $rawName   = preg_replace('/^\d+\.\s*/', '', $label);
                $queryName = preg_replace('/\s*\([^)]+\)\s*$/', '', $rawName);
                $logoUrl   = '';

                // ── Strategy 1: DuckDuckGo Instant Answer (Wikipedia-backed logos) ──
                try {
                    $ddgUrl = 'https://api.duckduckgo.com/?q=' . urlencode($queryName)
                            . '&format=json&no_html=1&skip_disambig=1';
                    $ch = curl_init($ddgUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 6,
                        CURLOPT_CONNECTTIMEOUT => 3,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; AI-mmi/1.0)',
                        CURLOPT_FOLLOWLOCATION => true,
                    ]);
                    $ddgResp = curl_exec($ch);
                    $ddgCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($ddgCode === 200 && $ddgResp) {
                        $ddg = json_decode($ddgResp, true);
                        // Only use type=A (article) results — not disambiguation (D) or empty
                        $ddgType = trim($ddg['Type'] ?? '');
                        $imgPath = trim($ddg['Image'] ?? '');
                        if ($ddgType === 'A' && $imgPath !== '') {
                            $logoUrl = 'https://duckduckgo.com' . $imgPath;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Student_Profile::fetch_logos DDG: ' . $e->getMessage());
                }

                // ── Strategy 2: Clearbit Autocomplete → Google favicon (fallback) ──
                if (empty($logoUrl)) {
                    try {
                        $acUrl = 'https://autocomplete.clearbit.com/v1/companies/suggest?query=' . urlencode($queryName);
                        $ch = curl_init($acUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_TIMEOUT        => 8,
                            CURLOPT_CONNECTTIMEOUT => 4,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_USERAGENT      => 'Mozilla/5.0',
                        ]);
                        $acResp = curl_exec($ch);
                        $acCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($acCode === 200 && $acResp) {
                            $suggestions = json_decode($acResp, true);
                            if (is_array($suggestions) && !empty($suggestions)) {
                                $domain = trim($suggestions[0]['domain'] ?? '');
                                // Reject parked/squatted domains
                                if ($domain && !preg_match($parkedDomainPattern, $domain)) {
                                    $logoUrl = 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=128';
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Student_Profile::fetch_logos Clearbit: ' . $e->getMessage());
                    }
                }

                $logos[$idx] = $logoUrl;
            }
        }

        // Save enriched JSON back to DB — always overwrite logo_url so stale/wrong logos get corrected
        $fieldName = $type === 'education' ? 'education_history' : 'work_experience';
        $profile   = DB::table('student_academic_profiles')->where('member_id', $memberId)->first();
        if ($profile) {
            $existing = json_decode($profile->$fieldName ?? '[]', true) ?: [];
            foreach ($logos as $i => $logoUrl) {
                if (isset($existing[$i]) && $logoUrl !== '') {
                    $existing[$i]['logo_url'] = $logoUrl;  // always overwrite
                }
            }
            DB::table('student_academic_profiles')
                ->where('member_id', $memberId)
                ->update([
                    $fieldName   => json_encode(array_values($existing)),
                    'updated_at' => now()->toDateTimeString(),
                    'updated_by' => $memberId,
                ]);
        }

        return response()->json(['status' => 200, 'logos' => array_values($logos)]);
    }

    // -------------------------------------------------------
    // POST /{lang}/student_profile/upload_entry_logo
    // -------------------------------------------------------
    public function upload_entry_logo()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }
        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }
        $file = request()->file('logo');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid file provided']);
        }
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            return response()->json(['status' => 400, 'message' => 'Invalid file type. Use JPG, PNG or WEBP.']);
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'File too large. Max 2MB.']);
        }
        $fileName = md5(uniqid((string)rand())) . '.' . $ext;
        $location = 'upload/entry_logos';
        if (!file_exists(public_path($location))) {
            @mkdir(public_path($location), 0755, true);
        }
        if (!$file->move(public_path($location), $fileName)) {
            return response()->json(['status' => 500, 'message' => 'Upload failed.']);
        }
        try {
            \Intervention\Image\Facades\Image::make(public_path($location.'/'.$fileName))
                ->fit(128, 128)->save(public_path($location.'/'.$fileName));
        } catch (\Exception $e) { /* keep original */ }
        return response()->json([
            'status'   => 200,
            'logo_url' => 'upload/entry_logos/' . $fileName,
        ]);
    }

    private function _sanitizeLogoUrl(string $url): string
    {
        $url = trim($url);
        if (empty($url)) return '';
        if ($url === '_removed_') return '_removed_';
        if (strpos($url, 'upload/entry_logos/') === 0) return $url;
        if (strpos($url, 'upload/member_avatar/') === 0) return $url;
        if (strpos($url, 'https://logo.clearbit.com/') === 0) return $url;
        if (preg_match('#^https?://[^<>"\s]+$#', $url) && strlen($url) <= 500) return $url;
        return '';
    }

    private function _clearbitLogoUrl(string $domain): string
    {
        $domain = preg_replace('#^https?://#', '', trim($domain));
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('/[^a-zA-Z0-9.\-]/', '', $domain);
        if (empty($domain) || strpos($domain, '.') === false) return '';
        $url = 'https://logo.clearbit.com/' . $domain;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($code >= 200 && $code < 300) ? $url : '';
    }

    private function _calcCompleteness($profile): int
    {
        if (!$profile) {
            return 0;
        }
        $score = 0;
        if (!empty($profile->headline))          $score += 15;
        if (!empty($profile->bio))               $score += 15;
        if (!empty($profile->nationality))        $score += 5;
        if (!empty($profile->current_country))    $score += 5;
        if (!empty($profile->target_degree))      $score += 10;
        if (!empty($profile->target_fields))      $score += 10;
        if (!empty($profile->target_intake_year)) $score += 5;
        if (!empty($profile->budget_usd_max))     $score += 5;
        if (!empty($profile->education_history) && $profile->education_history !== '[]') $score += 15;
        if (!empty($profile->language_scores)  && $profile->language_scores  !== '[]') $score += 10;
        if (!empty($profile->achievements)     && $profile->achievements     !== '[]') $score += 5;
        return min(100, $score);
    }
}

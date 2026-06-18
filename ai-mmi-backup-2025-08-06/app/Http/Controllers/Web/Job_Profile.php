<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job_Profile — job seeker profile (similar to student academic profile).
 *
 * Routes:
 *   GET  /{lang}/job_profile           → index()
 *   POST /{lang}/job_profile/save      → save()
 *   POST /{lang}/job_profile/upload_resume → upload_resume()
 */
class Job_Profile extends WebController
{
    public function index()
    {
        if (empty($this->_current_member)) {
            return $this->pageData(['is_guest' => true])->pageView('job_profile');
        }

        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            $this->doRedirect($this->toURL('job_applications'));
            return;
        }

        $memberId = (int) $member['id'];
        $profile  = DB::table('job_seeker_profiles')->where('member_id', $memberId)->first();
        $completeness = $this->_calcCompleteness($profile);

        return $this->pageData([
            'profile'      => $profile,
            'completeness' => $completeness,
        ])->pageView('job_profile');
    }

    public function save()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }

        $memberId   = (int) $member['id'];
        $section    = (string) request()->input('section', '');
        $data       = (array) request()->input('data', []);
        $updateData = $this->_buildUpdateData($section, $data);

        if ($updateData === null) {
            return response()->json(['status' => 400, 'message' => 'Unknown section']);
        }

        $updateData['updated_by'] = $memberId;
        $updateData['updated_at'] = now()->toDateTimeString();

        $exists = DB::table('job_seeker_profiles')->where('member_id', $memberId)->exists();

        try {
            if ($exists) {
                DB::table('job_seeker_profiles')->where('member_id', $memberId)->update($updateData);
            } else {
                $updateData['member_id']  = $memberId;
                $updateData['created_by'] = $memberId;
                $updateData['created_at'] = now()->toDateTimeString();
                $updateData['profile_views'] = 0;
                $updateData['status']     = 1;
                DB::table('job_seeker_profiles')->insert($updateData);
            }
        } catch (\Exception $e) {
            Log::error('Job_Profile::save ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Save failed.']);
        }

        $profile = DB::table('job_seeker_profiles')->where('member_id', $memberId)->first();

        return response()->json([
            'status'       => 200,
            'message'      => 'Saved successfully',
            'completeness' => $this->_calcCompleteness($profile),
        ]);
    }

    public function upload_resume()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        $member = $this->_current_member;
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Not allowed']);
        }

        $file = request()->file('resume');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid file provided']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            return response()->json(['status' => 400, 'message' => 'Use PDF, DOC, or DOCX.']);
        }
        if ($file->getSize() > 8 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'Max file size is 8MB.']);
        }

        $memberId = (int) $member['id'];
        $dir      = 'upload/job_resumes';
        if (!file_exists(public_path($dir))) {
            @mkdir(public_path($dir), 0755, true);
        }

        $fileName = $memberId . '_' . md5(uniqid((string) rand(), true)) . '.' . $ext;
        if (!$file->move(public_path($dir), $fileName)) {
            return response()->json(['status' => 500, 'message' => 'Upload failed.']);
        }

        $path = $dir . '/' . $fileName;
        $now  = now()->toDateTimeString();

        $exists = DB::table('job_seeker_profiles')->where('member_id', $memberId)->exists();
        if ($exists) {
            DB::table('job_seeker_profiles')->where('member_id', $memberId)->update([
                'resume_path' => $path,
                'updated_by'  => $memberId,
                'updated_at'  => $now,
            ]);
        } else {
            DB::table('job_seeker_profiles')->insert([
                'member_id'     => $memberId,
                'resume_path'   => $path,
                'profile_views' => 0,
                'status'        => 1,
                'created_by'    => $memberId,
                'created_at'    => $now,
                'updated_by'    => $memberId,
                'updated_at'    => $now,
            ]);
        }

        return response()->json([
            'status'       => 200,
            'message'      => 'Resume uploaded',
            'resume_path'  => '/' . $path,
            'completeness' => $this->_calcCompleteness(DB::table('job_seeker_profiles')->where('member_id', $memberId)->first()),
        ]);
    }

    private function _buildUpdateData(string $section, array $data): ?array
    {
        switch ($section) {
            case 'hero':
                return [
                    'headline'        => mb_substr(strip_tags($data['headline'] ?? ''), 0, 300),
                    'nationality'     => mb_substr(strip_tags($data['nationality'] ?? ''), 0, 100),
                    'current_country' => mb_substr(strip_tags($data['current_country'] ?? ''), 0, 100),
                    'current_city'    => mb_substr(strip_tags($data['current_city'] ?? ''), 0, 100),
                    'open_to_work'    => mb_substr(strip_tags($data['open_to_work'] ?? ''), 0, 50),
                ];

            case 'bio':
                return ['bio' => mb_substr(strip_tags($data['bio'] ?? ''), 0, 2000)];

            case 'preferences':
                $roles = array_slice(array_map(
                    fn ($f) => mb_substr(strip_tags((string) $f), 0, 100),
                    (array) ($data['target_roles'] ?? [])
                ), 0, 10);
                $locs = array_slice(array_map(
                    fn ($f) => mb_substr(strip_tags((string) $f), 0, 100),
                    (array) ($data['target_locations'] ?? [])
                ), 0, 10);
                return [
                    'target_roles'          => json_encode(array_values(array_filter($roles))),
                    'target_locations'      => json_encode(array_values(array_filter($locs))),
                    'employment_preference' => mb_substr(strip_tags($data['employment_preference'] ?? ''), 0, 50),
                ];

            case 'education':
                $entries = array_slice((array) ($data['entries'] ?? []), 0, 10);
                $clean = array_map(fn ($e) => [
                    'degree'      => mb_substr(strip_tags($e['degree'] ?? ''), 0, 100),
                    'institution' => mb_substr(strip_tags($e['institution'] ?? ''), 0, 200),
                    'field'       => mb_substr(strip_tags($e['field'] ?? ''), 0, 200),
                    'country'     => mb_substr(strip_tags($e['country'] ?? ''), 0, 100),
                    'grad_year'   => mb_substr(strip_tags($e['grad_year'] ?? ''), 0, 10),
                ], $entries);
                return ['education_history' => json_encode(array_values($clean))];

            case 'work':
                $entries = array_slice((array) ($data['entries'] ?? []), 0, 10);
                $clean = array_map(fn ($e) => [
                    'title'       => mb_substr(strip_tags($e['title'] ?? ''), 0, 200),
                    'company'     => mb_substr(strip_tags($e['company'] ?? ''), 0, 200),
                    'country'     => mb_substr(strip_tags($e['country'] ?? ''), 0, 100),
                    'from'        => mb_substr(strip_tags($e['from'] ?? ''), 0, 20),
                    'to'          => mb_substr(strip_tags($e['to'] ?? ''), 0, 20),
                    'current'     => (bool) ($e['current'] ?? false),
                    'description' => mb_substr(strip_tags($e['description'] ?? ''), 0, 500),
                ], $entries);
                return ['work_experience' => json_encode(array_values($clean))];

            case 'skills':
                $skills = array_slice(array_map(
                    fn ($s) => mb_substr(strip_tags((string) $s), 0, 80),
                    (array) ($data['skills'] ?? [])
                ), 0, 30);
                return ['skills' => json_encode(array_values(array_filter($skills)))];

            case 'language':
                $entries = array_slice((array) ($data['entries'] ?? []), 0, 10);
                $clean = array_map(fn ($e) => [
                    'test'  => mb_substr(strip_tags($e['test'] ?? ''), 0, 50),
                    'score' => mb_substr(strip_tags($e['score'] ?? ''), 0, 20),
                ], $entries);
                return ['language_scores' => json_encode(array_values($clean))];
        }

        return null;
    }

    private function _calcCompleteness($profile): int
    {
        if (!$profile) {
            return 0;
        }
        $fields = ['headline', 'bio', 'current_country', 'work_experience', 'skills', 'education_history'];
        $filled = 0;
        foreach ($fields as $f) {
            $val = $profile->$f ?? '';
            if (is_string($val) && trim($val) !== '' && trim($val) !== '[]') {
                $filled++;
            }
        }
        if (!empty($profile->resume_path)) {
            $filled++;
        }
        return (int) round(($filled / 7) * 100);
    }
}

<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job_Applications — LinkedIn-style job search & applications hub.
 *
 * Routes (auto via RouteMapping):
 *   GET  /{lang}/job_applications           → index()
 *   POST /{lang}/job_applications/apply       → apply()
 *   POST /{lang}/job_applications/post_job    → post_job()   (admin only)
 *   POST /{lang}/job_applications/delete_job  → delete_job() (admin only)
 */
class Job_Applications extends WebController
{
    private const JOB_ADMIN_EMAIL = 'admin@wealthskey.com';

    public function index()
    {
        $member   = $this->_current_member;
        $memberId = (int)($member['id'] ?? 0);
        $query    = trim((string)($this->_page_get_data['q'] ?? ''));
        $country  = trim((string)($this->_page_get_data['country'] ?? ''));
        $type     = trim((string)($this->_page_get_data['type'] ?? ''));

        $jobsQuery = DB::table('job_postings')
            ->where('status', 1)
            ->whereNull('deleted_at');

        if ($query !== '') {
            $jobsQuery->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('company_name', 'like', '%' . $query . '%')
                  ->orWhere('description', 'like', '%' . $query . '%');
            });
        }
        if ($country !== '') {
            $jobsQuery->where('country', 'like', '%' . $country . '%');
        }
        if ($type !== '') {
            $jobsQuery->where('employment_type', $type);
        }

        $jobs = $jobsQuery
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();

        $appliedIds = [];
        $myApplications = [];
        if ($memberId > 0) {
            $rows = DB::table('job_applications')
                ->where('member_id', $memberId)
                ->orderByDesc('submitted_at')
                ->get();
            foreach ($rows as $row) {
                $appliedIds[] = (int) $row->job_posting_id;
                $myApplications[] = (array) $row;
            }
        }

        $profile = null;
        $profileCompleteness = 0;
        if ($memberId > 0 && (int)($member['type'] ?? 0) === 1) {
            $profile = DB::table('job_seeker_profiles')->where('member_id', $memberId)->first();
            $profileCompleteness = $this->_calcProfileCompleteness($profile);
        }

        return $this->pageData([
            'jobs'                 => $jobs,
            'applied_ids'            => $appliedIds,
            'my_applications'        => $myApplications,
            'profile'                => $profile,
            'profile_completeness'   => $profileCompleteness,
            'search_q'               => $query,
            'search_country'         => $country,
            'search_type'            => $type,
            'is_job_admin'           => $this->isJobAdmin(),
            'is_guest'               => empty($member),
        ])->pageView('job_applications');
    }

    public function apply()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        $member = $this->_current_member;
        if (empty($member) || (int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Please log in as an individual account to apply.']);
        }

        $jobId = (int) request()->input('job_id', 0);
        if ($jobId <= 0) {
            return response()->json(['status' => 400, 'message' => 'Invalid job.']);
        }

        $job = DB::table('job_postings')
            ->where('id', $jobId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$job) {
            return response()->json(['status' => 404, 'message' => 'Job not found.']);
        }

        $memberId = (int) $member['id'];

        $exists = DB::table('job_applications')
            ->where('job_posting_id', $jobId)
            ->where('member_id', $memberId)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 409, 'message' => 'You have already applied to this job.']);
        }

        $profile = DB::table('job_seeker_profiles')->where('member_id', $memberId)->first();
        $coverLetter = mb_substr(strip_tags((string) request()->input('cover_letter', '')), 0, 3000);

        $snapshot = null;
        if ($profile) {
            $snapshot = json_encode([
                'headline'         => $profile->headline ?? '',
                'current_country'  => $profile->current_country ?? '',
                'skills'           => $profile->skills ?? '[]',
                'work_experience'  => $profile->work_experience ?? '[]',
            ]);
        }

        try {
            DB::table('job_applications')->insert([
                'job_posting_id'   => $jobId,
                'member_id'        => $memberId,
                'cover_letter'     => $coverLetter ?: null,
                'resume_path'      => $profile->resume_path ?? null,
                'profile_snapshot' => $snapshot,
                'status'           => 'submitted',
                'submitted_at'     => now()->toDateTimeString(),
                'created_by'       => $memberId,
                'created_at'       => now()->toDateTimeString(),
                'updated_by'       => $memberId,
                'updated_at'       => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Job_Applications::apply ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Application failed. Please try again.']);
        }

        return response()->json(['status' => 200, 'message' => 'Application submitted successfully!']);
    }

    public function post_job()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        if (!$this->isJobAdmin()) {
            return response()->json(['status' => 403, 'message' => 'Only the admin can post jobs.']);
        }

        $title = mb_substr(strip_tags((string) request()->input('title', '')), 0, 300);
        if ($title === '') {
            return response()->json(['status' => 400, 'message' => 'Job title is required.']);
        }

        $adminId = (int) ($this->_current_member['id'] ?? 0);
        $now     = now()->toDateTimeString();

        $data = [
            'posted_by'        => $adminId,
            'title'            => $title,
            'company_name'     => mb_substr(strip_tags((string) request()->input('company_name', '')), 0, 200) ?: null,
            'country'          => mb_substr(strip_tags((string) request()->input('country', '')), 0, 100) ?: null,
            'city'             => mb_substr(strip_tags((string) request()->input('city', '')), 0, 100) ?: null,
            'location_type'    => mb_substr(strip_tags((string) request()->input('location_type', 'on_site')), 0, 50),
            'employment_type'  => mb_substr(strip_tags((string) request()->input('employment_type', 'full_time')), 0, 50),
            'description'      => mb_substr(strip_tags((string) request()->input('description', '')), 0, 8000) ?: null,
            'requirements'     => mb_substr(strip_tags((string) request()->input('requirements', '')), 0, 4000) ?: null,
            'salary_min'       => ($v = (int) request()->input('salary_min', 0)) > 0 ? $v : null,
            'salary_max'       => ($v = (int) request()->input('salary_max', 0)) > 0 ? $v : null,
            'salary_currency'  => mb_substr(strip_tags((string) request()->input('salary_currency', 'USD')), 0, 10),
            'visa_sponsorship' => (int) (bool) request()->input('visa_sponsorship', 0),
            'application_url'  => filter_var(request()->input('application_url', ''), FILTER_VALIDATE_URL) ?: null,
            'status'           => 1,
            'views'            => 0,
            'created_by'       => $adminId,
            'created_at'       => $now,
            'updated_by'       => $adminId,
            'updated_at'       => $now,
        ];

        try {
            $id = DB::table('job_postings')->insertGetId($data);
        } catch (\Exception $e) {
            Log::error('Job_Applications::post_job ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Could not create job posting.']);
        }

        return response()->json(['status' => 200, 'message' => 'Job posted successfully.', 'job_id' => $id]);
    }

    public function delete_job()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        if (!$this->isJobAdmin()) {
            return response()->json(['status' => 403, 'message' => 'Unauthorized']);
        }

        $jobId = (int) request()->input('job_id', 0);
        if ($jobId <= 0) {
            return response()->json(['status' => 400, 'message' => 'Invalid job.']);
        }

        $adminId = (int) ($this->_current_member['id'] ?? 0);
        DB::table('job_postings')
            ->where('id', $jobId)
            ->update([
                'status'     => 0,
                'deleted_by' => $adminId,
                'deleted_at' => now()->toDateTimeString(),
                'updated_by' => $adminId,
                'updated_at' => now()->toDateTimeString(),
            ]);

        return response()->json(['status' => 200, 'message' => 'Job removed.']);
    }

    private function isJobAdmin(): bool
    {
        $email = mb_strtolower(trim((string) ($this->_current_member['email'] ?? '')), 'UTF-8');
        if ($email === self::JOB_ADMIN_EMAIL) {
            return true;
        }
        $realEmail = mb_strtolower(trim((string) $this->getSession('admin_real_email')), 'UTF-8');
        return $realEmail === self::JOB_ADMIN_EMAIL;
    }

    private function _calcProfileCompleteness($profile): int
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

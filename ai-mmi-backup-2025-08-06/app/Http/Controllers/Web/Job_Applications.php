<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use App\Services\CompanyLogoFetcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job_Applications — LinkedIn-style job search & applications hub.
 */
class Job_Applications extends WebController
{
    private const JOB_ADMIN_EMAIL = 'admin@wealthskey.com';

    private const NOTIFY_EMAILS = ['admin@wealthskey.com', 'info@ai-mmi.com'];

    public function index()
    {
        $member   = $this->_current_member;
        $memberId = (int)($member['id'] ?? 0);
        $memberType = (int)($member['type'] ?? 0);
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
            $jobsQuery->where(function ($q) use ($country) {
                $q->where('country', 'like', '%' . $country . '%')
                  ->orWhere('city', 'like', '%' . $country . '%');
            });
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
            $rows = DB::table('job_applications as ja')
                ->leftJoin('job_postings as jp', 'jp.id', '=', 'ja.job_posting_id')
                ->where('ja.member_id', $memberId)
                ->orderByDesc('ja.submitted_at')
                ->select(
                    'ja.*',
                    'jp.title as job_title',
                    'jp.company_name as job_company',
                    'jp.country as job_country',
                    'jp.city as job_city'
                )
                ->get();

            foreach ($rows as $row) {
                $appliedIds[] = (int) $row->job_posting_id;
                $myApplications[] = (array) $row;
            }
        }

        $profile = null;
        $profileCompleteness = 0;
        if ($memberId > 0 && $memberType === 1) {
            $profile = DB::table('job_seeker_profiles')->where('member_id', $memberId)->first();
            $profileCompleteness = $this->_calcProfileCompleteness($profile);
        }

        return $this->pageData([
            'jobs'                   => $jobs,
            'job_count'              => count($jobs),
            'applied_ids'            => $appliedIds,
            'my_applications'        => $myApplications,
            'profile'                => $profile,
            'profile_completeness'   => $profileCompleteness,
            'search_q'               => $query,
            'search_country'         => $country,
            'search_type'            => $type,
            'is_job_admin'           => $this->isJobAdmin(),
            'is_guest'               => empty($member),
            'can_apply'              => $memberId > 0 && $memberType === 1,
            'member_type'            => $memberType,
        ])->pageView('job_applications');
    }

    public function apply()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }

        $member = $this->_current_member;
        if (empty($member)) {
            return response()->json(['status' => 401, 'message' => 'Please sign in to apply for jobs.']);
        }
        if ((int)($member['type'] ?? 0) !== 1) {
            return response()->json(['status' => 403, 'message' => 'Only individual accounts can apply. Please use a student/job seeker account.']);
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
            return response()->json(['status' => 404, 'message' => 'This job is no longer available.']);
        }

        if (!empty($job->application_url)) {
            return response()->json([
                'status'  => 400,
                'message' => 'This job uses an external application link.',
                'external_url' => $job->application_url,
            ]);
        }

        $memberId = (int) $member['id'];

        if (DB::table('job_applications')->where('job_posting_id', $jobId)->where('member_id', $memberId)->exists()) {
            return response()->json(['status' => 409, 'message' => 'You have already applied to this job.']);
        }

        $profile = DB::table('job_seeker_profiles')->where('member_id', $memberId)->first();
        $coverLetter = mb_substr(strip_tags((string) request()->input('cover_letter', '')), 0, 3000);

        $snapshot = json_encode([
            'headline'        => $profile->headline ?? '',
            'bio'             => $profile->bio ?? '',
            'current_country' => $profile->current_country ?? '',
            'current_city'    => $profile->current_city ?? '',
            'skills'          => $profile->skills ?? '[]',
            'work_experience' => $profile->work_experience ?? '[]',
            'resume_path'     => $profile->resume_path ?? '',
        ]);

        $now = now()->toDateTimeString();

        try {
            $applicationId = DB::table('job_applications')->insertGetId([
                'job_posting_id'   => $jobId,
                'member_id'        => $memberId,
                'cover_letter'     => $coverLetter ?: null,
                'resume_path'      => $profile->resume_path ?? null,
                'profile_snapshot' => $snapshot,
                'status'           => 'submitted',
                'submitted_at'     => $now,
                'created_by'       => $memberId,
                'created_at'       => $now,
                'updated_by'       => $memberId,
                'updated_at'       => $now,
            ]);
        } catch (\Exception $e) {
            Log::error('Job_Applications::apply ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Application failed. Please try again.']);
        }

        $this->_notifyApplicationSubmitted($member, $job, $coverLetter, $profile, (int) $applicationId);

        return response()->json([
            'status'         => 200,
            'message'        => 'Application submitted! The employer has been notified.',
            'application_id' => $applicationId,
        ]);
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

        $company = mb_substr(strip_tags((string) request()->input('company_name', '')), 0, 200);
        if ($company === '') {
            return response()->json(['status' => 400, 'message' => 'Company name is required.']);
        }

        $adminId = (int) ($this->_current_member['id'] ?? 0);
        $now     = now()->toDateTimeString();

        $externalUrl = trim((string) request()->input('application_url', ''));
        if ($externalUrl !== '' && !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'Please enter a valid external application URL.']);
        }

        $companyWebsite = trim((string) request()->input('company_website', ''));
        if ($companyWebsite !== '' && !filter_var($companyWebsite, FILTER_VALIDATE_URL) && !filter_var('https://' . $companyWebsite, FILTER_VALIDATE_URL)) {
            return response()->json(['status' => 400, 'message' => 'Please enter a valid company website URL.']);
        }

        $fetcher = new CompanyLogoFetcher('upload/job_logos');
        $logoPath = trim((string) request()->input('company_logo', ''));
        if ($logoPath !== '' && !$fetcher->isValidStoredPath($logoPath)) {
            $logoPath = '';
        }

        $autoFetch = (int) request()->input('auto_fetch_logo', 0) === 1;
        if ($logoPath === '' && $autoFetch) {
            $derivedWebsite = $companyWebsite;
            if ($derivedWebsite === '' && $externalUrl !== '') {
                $derivedWebsite = $externalUrl;
            }
            $fetched = $fetcher->fetch($company, $derivedWebsite ?: null);
            if ($fetched) {
                $logoPath = $fetched['relative_path'];
            }
        }

        $data = [
            'posted_by'        => $adminId,
            'title'            => $title,
            'company_name'     => $company,
            'company_logo'     => $logoPath !== '' ? $logoPath : null,
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
            'application_url'  => $externalUrl !== '' ? $externalUrl : null,
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

    /**
     * Preview-fetch a company logo (admin only).
     * POST /{lang}/job_applications/fetch_company_logo
     */
    public function fetch_company_logo()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }
        if (!$this->isJobAdmin()) {
            return response()->json(['status' => 403, 'message' => 'Unauthorized']);
        }

        $company = mb_substr(strip_tags((string) request()->input('company_name', '')), 0, 200);
        if ($company === '') {
            return response()->json(['status' => 400, 'message' => 'Company name is required to fetch a logo.']);
        }

        $website = trim((string) request()->input('company_website', ''));
        $fetcher = new CompanyLogoFetcher('upload/job_logos');
        $result  = $fetcher->fetch($company, $website ?: null);

        if (!$result) {
            return response()->json([
                'status'  => 404,
                'message' => 'No confident logo found for this company. Try adding the company website, upload a logo manually, or publish without one.',
            ]);
        }

        return response()->json([
            'status'       => 200,
            'message'      => 'Logo found.',
            'logo_url'     => $result['url'],
            'company_logo' => $result['relative_path'],
            'warning'      => $result['warning'],
        ]);
    }

    /**
     * Manual company logo upload (admin only).
     * POST /{lang}/job_applications/upload_company_logo  (multipart)
     */
    public function upload_company_logo()
    {
        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405, 'message' => 'Method not allowed']);
        }
        if (!$this->isJobAdmin()) {
            return response()->json(['status' => 403, 'message' => 'Unauthorized']);
        }

        $file = request()->file('logo_file');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 400, 'message' => 'No valid image uploaded.']);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)) {
            return response()->json(['status' => 400, 'message' => 'Use PNG, JPG, GIF, WEBP, or SVG.']);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['status' => 400, 'message' => 'Max file size is 5 MB.']);
        }

        $fetcher = new CompanyLogoFetcher('upload/job_logos');
        $result  = $fetcher->saveUploadedFile($file);
        if (!$result) {
            return response()->json(['status' => 500, 'message' => 'Could not save logo.']);
        }

        return response()->json([
            'status'       => 200,
            'message'      => 'Logo uploaded.',
            'logo_url'     => $result['url'],
            'company_logo' => $result['relative_path'],
        ]);
    }

    private function _notifyApplicationSubmitted($member, $job, string $coverLetter, $profile, int $applicationId): void
    {
        $applicantName = trim($member['alias_name'] ?? '') ?: trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
        $applicantEmail = trim($member['email'] ?? '');
        $jobTitle = $job->title ?? 'Job';
        $company = $job->company_name ?? 'Company';
        $location = trim(($job->city ?? '') . (($job->city && $job->country) ? ', ' : '') . ($job->country ?? ''));
        $headline = $profile->headline ?? '';
        $resumePath = $profile->resume_path ?? '';
        $resumeLink = $resumePath ? url('/' . ltrim($resumePath, '/')) : '';

        $adminSubject = 'New Job Application — ' . $jobTitle . ' @ ' . $company;
        $adminBody = '<h2 style="margin:0 0 12px;color:#0a66c2;">New job application #' . $applicationId . '</h2>'
            . '<p><strong>Job:</strong> ' . htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Company:</strong> ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '</p>'
            . ($location ? '<p><strong>Location:</strong> ' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '</p>' : '')
            . '<hr style="border:none;border-top:1px solid #ddd;margin:16px 0;">'
            . '<p><strong>Applicant:</strong> ' . htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><strong>Email:</strong> ' . htmlspecialchars($applicantEmail, ENT_QUOTES, 'UTF-8') . '</p>'
            . ($headline ? '<p><strong>Headline:</strong> ' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '</p>' : '')
            . ($resumeLink ? '<p><strong>Resume:</strong> <a href="' . htmlspecialchars($resumeLink, ENT_QUOTES, 'UTF-8') . '">View resume</a></p>' : '<p><em>No resume uploaded</em></p>')
            . ($coverLetter ? '<p><strong>Cover letter:</strong><br>' . nl2br(htmlspecialchars($coverLetter, ENT_QUOTES, 'UTF-8')) . '</p>' : '')
            . '<p style="margin-top:20px;font-size:12px;color:#666;">Submitted via AI-mmi Job Applications</p>';

        try {
            $this->sendEmail(self::NOTIFY_EMAILS, $adminSubject, $adminBody);
        } catch (\Throwable $e) {
            Log::error('Job application admin email failed: ' . $e->getMessage());
        }

        if ($applicantEmail !== '' && filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            $userSubject = 'Application received — ' . $jobTitle;
            $userBody = '<h2 style="margin:0 0 12px;color:#0a66c2;">We received your application</h2>'
                . '<p>Hi ' . htmlspecialchars($applicantName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your application for <strong>' . htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') . '</strong> at '
                . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . ' has been submitted successfully.</p>'
                . '<p>The hiring team will review your profile and contact you if you are shortlisted.</p>'
                . '<p style="margin-top:20px;font-size:12px;color:#666;">AI-mmi Job Applications</p>';
            try {
                $this->sendEmail($applicantEmail, $userSubject, $userBody);
            } catch (\Throwable $e) {
                Log::error('Job application user email failed: ' . $e->getMessage());
            }
        }
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

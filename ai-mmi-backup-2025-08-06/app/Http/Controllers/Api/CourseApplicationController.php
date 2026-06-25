<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseApplicationController extends Controller
{
    /**
     * Persist a course application (create or update).
     */
    public function store(Request $request)
    {
        try {
            $memberId = $this->getMemberId();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Authentication required',
            ], 401);
        }

        $intent = $request->input('intent', 'submit');
        $validator = Validator::make($request->all(), $this->rules($intent), [], $this->attributes());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $application = null;
        if (!empty($validated['application_id'])) {
            $application = CourseApplication::where('id', $validated['application_id'])
                ->where('member_id', $memberId)
                ->first();
        }

        if (!$application) {
            $application = new CourseApplication();
            $application->member_id = $memberId;
        }

        $englishTests = $this->filterEmptyValues($request->input('english_tests', []));
        $scholarships = $intent === 'save'
            ? ($request->input('scholarship_colleges', []) ?? [])
            : array_values(array_unique(array_filter($request->input('scholarship_colleges', []) ?? [])));

        $documentMeta = $this->handleDocuments($request, $memberId, $application->document_paths ?? []);
        $hasEnglishTest = ($validated['has_english_test'] ?? 'no') === 'yes';
        $hasFinancialSupport = ($validated['has_financial_support'] ?? 'no') === 'yes';
        $wantsScholarship = ($validated['wants_scholarship'] ?? 'no') === 'yes';
        if (!$wantsScholarship) {
            $scholarships = [];
        }

        $application->fill([
            'family_name'         => $validated['family_name'] ?? null,
            'given_name'          => $validated['given_name'] ?? null,
            'email_address'       => $validated['email_address'] ?? null,
            'mobile_number'       => $validated['mobile_number'] ?? null,
            'residential_address' => $validated['residential_address'] ?? null,
            'date_of_birth'       => $validated['date_of_birth'] ?? null,
            'nationality'         => $validated['nationality'] ?? null,
            'highest_education'   => $validated['highest_education'] ?? null,
            'has_english_test'    => $hasEnglishTest,
            'english_tests'       => $englishTests,
            'has_financial_support' => $hasFinancialSupport,
            'financial_notes'     => $validated['financial_notes'] ?? null,
            'target_institution'  => $validated['target_institution'] ?? null,
            'target_program'      => $validated['target_program'] ?? null,
            'start_year'          => $validated['start_year'] ?? null,
            'wants_scholarship'   => $wantsScholarship,
            'scholarship_colleges'=> $scholarships,
            'document_paths'      => $documentMeta,
        ]);

        $application->status = ($intent === 'save') ? 'draft' : 'submitted';
        if ($application->status === 'submitted') {
            $application->submitted_at = now();
        }

        $application->save();

        if ($intent === 'submit') {
            $fresh = $application->fresh();
            try {
                $this->sendTeamNotification($fresh);
            } catch (\Throwable $e) {
                \Log::error('Failed to send team notification email: ' . $e->getMessage());
            }
            try {
                $this->sendApplicantConfirmation($fresh);
            } catch (\Throwable $e) {
                \Log::error('Failed to send applicant confirmation email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => ($intent === 'save') ? 'Application saved' : 'Application submitted',
            'application' => $this->formatApplication($application->fresh()),
        ], $application->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Fetch the latest application for the authenticated member.
     */
    public function latest(Request $request)
    {
        try {
            $memberId = $this->getMemberId();
        } catch (\Exception $e) {
            return response()->json([
                'application' => null,
            ], 200);
        }

        $application = CourseApplication::where('member_id', $memberId)
            ->latest('updated_at')
            ->first();

        if (!$application) {
            return response()->json(['application' => null], 200);
        }

        return response()->json([
            'application' => $this->formatApplication($application),
        ], 200);
    }

    private function rules(string $intent = 'submit'): array
    {
        $fieldRule = $intent === 'save' ? 'nullable' : 'required';

        return [
            'application_id'      => 'nullable|integer',
            'family_name'         => [$fieldRule, 'string', 'max:255'],
            'given_name'          => [$fieldRule, 'string', 'max:255'],
            'email_address'       => [$fieldRule, 'email', 'max:255'],
            'mobile_number'       => [$fieldRule, 'string', 'max:50'],
            'residential_address' => [$fieldRule, 'string', 'max:500'],
            'date_of_birth'       => [$fieldRule, 'date'],
            'nationality'         => [$fieldRule, 'string', 'max:255'],
            'highest_education'   => [$fieldRule, 'string', 'max:255'],
            'has_english_test'    => [$fieldRule, 'in:yes,no'],
            'english_tests'       => 'array',
            'english_tests.*'     => 'nullable|string|max:255',
            'has_financial_support' => [$fieldRule, 'in:yes,no'],
            'financial_notes'     => 'nullable|string|max:1000',
            'target_institution'  => [$fieldRule, 'string', 'max:255'],
            'target_program'      => [$fieldRule, 'string', 'max:255'],
            'start_year'          => [$fieldRule, 'string', 'max:10'],
            'wants_scholarship'   => [$fieldRule, 'in:yes,no'],
            'scholarship_colleges'=> 'array',
            'scholarship_colleges.*' => 'nullable|string|max:255',
            'passport_copy'       => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'education_certificate' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'english_test_result' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'financial_statement' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg|max:10240',
            'intent'              => 'nullable|in:save,submit',
        ];
    }

    private function attributes(): array
    {
        return [
            'family_name' => 'Family Name',
            'given_name' => 'Given Name',
            'email_address' => 'Email address',
            'mobile_number' => 'Mobile number',
            'residential_address' => 'Residential address',
            'date_of_birth' => 'Date of Birth',
            'nationality' => 'Nationality',
            'highest_education' => 'Highest education completed',
            'has_english_test' => 'English test status',
            'has_financial_support' => 'Financial support confirmation',
            'target_institution' => 'University/college',
            'target_program' => 'Program/course',
            'start_year' => 'Preferred start year',
            'wants_scholarship' => 'Scholarship preference',
        ];
    }

    private function handleDocuments(Request $request, int $memberId, array $existing = []): array
    {
        $documentFields = [
            'passport_copy'        => 'Copy of your passport',
            'education_certificate'=> 'Copy of your education certificate',
            'english_test_result'  => 'Copy of your English test result',
            'financial_statement'  => 'Copy of bank statement / financial proof',
        ];

        foreach ($documentFields as $field => $label) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $storedFileName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
                $originalName = $file->getClientOriginalName();
                $fileSize = $file->getSize();
                $uploadPath = 'upload/course_applications/'.$memberId;
                $fullPath = public_path($uploadPath);

                if (!is_dir($fullPath)) {
                    @mkdir($fullPath, 0755, true);
                }

                if (!empty($existing[$field]['path'])) {
                    $oldPath = public_path($existing[$field]['path']);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $file->move($fullPath, $storedFileName);

                $existing[$field] = [
                    'label'         => $label,
                    'original_name' => $originalName,
                    'stored_name'   => $storedFileName,
                    'path'          => $uploadPath.'/'.$storedFileName,
                    'size'          => $fileSize,
                    'uploaded_at'   => now()->toDateTimeString(),
                ];
            }
        }

        return $existing;
    }

    private function filterEmptyValues(array $values): array
    {
        $clean = [];
        foreach ($values as $key => $value) {
            if (!is_null($value) && $value !== '') {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    private function formatApplication(CourseApplication $application): array
    {
        $data = $application->toArray();
        $data['documents'] = $this->formatDocuments($application->document_paths ?? []);
        unset($data['document_paths']);

        return $data;
    }

    private function formatDocuments(array $documents): array
    {
        $formatted = [];
        foreach ($documents as $key => $doc) {
            $formatted[$key] = array_merge($doc, [
                'url' => !empty($doc['path']) ? asset($doc['path']) : null,
            ]);
        }
        return $formatted;
    }

    private function getMemberId()
    {
        $token = session('member_access_token') ?? request()->cookie('member_access_token');

        if (!$token) {
            throw new \Exception('No authentication token');
        }

        $tokenRecord = \DB::table('member_token')
            ->where('type', 1)
            ->where('value', $token)
            ->first();

        if (!$tokenRecord) {
            throw new \Exception('Invalid authentication token');
        }

        return (int) $tokenRecord->member_id;
    }

    private function sendTeamNotification($application): void
    {
        $documents    = json_decode($application->document_paths ?? '[]', true) ?: [];
        $englishTests = json_decode($application->english_tests ?? '[]', true) ?: [];
        $scholarships = json_decode($application->scholarship_colleges ?? '[]', true) ?: [];

        $summary = [
            'Applicant name'       => trim(($application->given_name ?? '') . ' ' . ($application->family_name ?? '')),
            'Email'                => $application->email_address ?? '-',
            'Mobile'               => $application->mobile_number ?? '-',
            'Date of birth'        => $application->date_of_birth ?? '-',
            'Nationality'          => $application->nationality ?? '-',
            'Highest education'    => $application->highest_education ?? '-',
            'Has English test'     => ($application->has_english_test ? 'Yes' : 'No'),
            'English tests'        => empty($englishTests) ? '-' : implode(', ', array_map(fn($k,$v) => strtoupper($k).': '.$v, array_keys($englishTests), $englishTests)),
            'Financial support'    => ($application->has_financial_support ? 'Yes' : 'No'),
            'Financial notes'      => $application->financial_notes ?? '-',
            'Target institution'   => $application->target_institution ?? '-',
            'Target program'       => $application->target_program ?? '-',
            'Preferred start year' => $application->start_year ?? '-',
            'Wants scholarship'    => ($application->wants_scholarship ? 'Yes' : 'No'),
            'Scholarship colleges' => empty($scholarships) ? '-' : implode(', ', $scholarships),
            'Residential address'  => nl2br(e($application->residential_address ?? '-')),
        ];

        $rows = '';
        foreach ($summary as $label => $value) {
            $rows .= '<tr><th align="left" style="padding:6px 10px;background:#f4f6fb;border:1px solid #dfe4ef;">'
                . e($label)
                . '</th><td style="padding:6px 10px;border:1px solid #dfe4ef;">'
                . (is_string($value) ? $value : e((string)$value))
                . '</td></tr>';
        }

        $docList = '';
        foreach ($documents as $doc) {
            $docList .= '<li>' . e($doc['label'] ?? $doc['original_name'] ?? 'Document')
                . ' - ' . e($doc['original_name'] ?? basename($doc['path'] ?? '')) . '</li>';
        }
        if (!$docList) $docList = '<li>No documents uploaded.</li>';

        $html = '<h2>New Course Application</h2>'
            . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;font-family:Arial,Helvetica,sans-serif;font-size:14px;">'
            . $rows . '</table>'
            . '<h3 style="margin-top:16px;">Documents</h3><ul>' . $docList . '</ul>';

        $attachments = [];
        foreach ($documents as $doc) {
            $filePath = !empty($doc['path']) ? public_path($doc['path']) : null;
            if ($filePath && file_exists($filePath)) {
                $attachments[] = ['path' => $filePath, 'name' => $doc['original_name'] ?? basename($filePath)];
            }
        }

        $applicantName = trim(($application->given_name ?? '') . ' ' . ($application->family_name ?? ''));
        $subject = 'New Course Application - ' . ($applicantName ?: ($application->email_address ?? 'Applicant'));
        $this->dispatchEmail('info@ai-mmi.com', null, $subject, $html, $attachments);
    }

    private function sendApplicantConfirmation($application): void
    {
        $toEmail = $application->email_address ?? null;
        if (empty($toEmail)) return;

        $firstName = $application->given_name ?? 'there';
        $institution = $application->target_institution ?? 'your chosen institution';
        $program = $application->target_program ?? 'your selected program';

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#333;max-width:580px;margin:0 auto;">'
            . '<h2 style="color:#0b2d6f;">Application Received</h2>'
            . '<p>Hi ' . e($firstName) . ',</p>'
            . '<p>Thank you for submitting your application to <strong>' . e($institution) . '</strong> for <strong>' . e($program) . '</strong>.</p>'
            . '<p>Our counsellors at AI-mmi have received your details and documents. We\'ll review everything and be in touch with you soon.</p>'
            . '<p>If you have any questions in the meantime, simply reply to this email.</p>'
            . '<p style="margin-top:24px;">Best regards,<br><strong>The AI-mmi Team</strong></p>'
            . '</div>';

        $this->dispatchEmail($toEmail, $firstName, 'Your AI-mmi Application — We\'ve received it!', $html);
    }

    private function dispatchEmail(string $toAddress, ?string $toName, string $subject, string $html, array $attachments = []): void
    {
        require_once app_path('Libraries/sendgrid/sendgrid-php.php');
        $fromAddress = env('MAIL_FROM_ADDRESS', 'no-reply@ai-mmi.com') ?: 'no-reply@ai-mmi.com';
        $fromName    = env('MAIL_FROM_NAME', 'AI-mmi') ?: 'AI-mmi';

        $email = new \SendGrid\Mail\Mail();
        $email->setFrom($fromAddress, $fromName);
        $email->setSubject($subject);
        $email->addTo($toAddress, $toName);
        $email->addContent('text/html', $html);

        foreach ($attachments as $file) {
            $contents = @file_get_contents($file['path']);
            if ($contents === false) continue;
            $mime = mime_content_type($file['path']) ?: 'application/octet-stream';
            $email->addAttachment(base64_encode($contents), $mime, $file['name'], 'attachment');
        }

        $apiKey = getenv('SENDGRID_API_KEY');
        if (empty($apiKey)) {
            \Log::error('SendGrid API key missing; cannot send email to ' . $toAddress);
            return;
        }

        $sendgrid = new \SendGrid($apiKey);
        $response = $sendgrid->send($email);
        \Log::info('Email dispatched', ['to' => $toAddress, 'subject' => $subject,
            'status' => method_exists($response, 'statusCode') ? $response->statusCode() : null]);
    }
}

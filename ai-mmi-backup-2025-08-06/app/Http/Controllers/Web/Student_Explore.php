<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Student_Explore — lets type=3 (institution) members browse and shortlist student profiles.
 *
 * Routes (auto via RouteMapping):
 *   GET  /{lang}/student_explore                  → index()        — browse students
 *   GET  /{lang}/student_explore/view/{id}        → view($id)      — view one student profile
 *   POST /{lang}/student_explore/interest         → interest()     — express interest
 *   GET  /{lang}/student_explore/my_interests     → my_interests() — institution's shortlist
 */
class Student_Explore extends WebController
{
    private int $_institutionId = 0;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        // Set institution ID if logged in as type=3; guests stay at 0 and see read-only view
        if (!empty($this->_current_member) && (int)($this->_current_member['type'] ?? 0) === 3) {
            $this->_institutionId = (int)$this->_current_member['id'];
        }
    }

    // -------------------------------------------------------
    // GET /{lang}/student_explore — browse students grid
    // -------------------------------------------------------
    public function index()
    {
        $degree      = request()->query('degree', '');
        $nationality = request()->query('nationality', '');
        $field       = request()->query('field', '');
        $lang_test   = request()->query('lang_test', '');

        $query = DB::table('student_academic_profiles as sap')
            ->join('member as m', 'm.id', '=', 'sap.member_id')
            ->where('m.type', 1)
            ->where('m.status', '>', 0)
            ->where('sap.status', 1)
            ->select(
                'sap.member_id', 'sap.headline', 'sap.nationality', 'sap.current_country',
                'sap.target_degree', 'sap.target_fields', 'sap.target_intake_year',
                'sap.language_scores', 'sap.profile_views',
                'm.alias_name', 'm.first_name', 'm.last_name', 'm.avatar'
            );

        if (!empty($degree)) {
            $query->where('sap.target_degree', $degree);
        }
        if (!empty($nationality)) {
            $query->where('sap.nationality', $nationality);
        }

        // Order: profiles with headline first (more complete), then by views
        $pfx = DB::getTablePrefix();
        $students = $query
            ->orderByRaw("CASE WHEN {$pfx}sap.headline IS NOT NULL AND {$pfx}sap.headline != '' THEN 0 ELSE 1 END")
            ->orderByDesc('sap.profile_views')
            ->paginate(12);

        // Institution's existing interests (to mark cards)
        $myInterestIds = DB::table('institution_interests')
            ->where('institution_member_id', $this->_institutionId)
            ->where('status', 1)
            ->pluck('student_member_id')
            ->toArray();

        // Filter options
        $nationalities = DB::table('student_academic_profiles')
            ->join('member as m', 'm.id', '=', 'student_academic_profiles.member_id')
            ->where('m.type', 1)->where('m.status', '>', 0)->where('student_academic_profiles.status', 1)
            ->whereNotNull('student_academic_profiles.nationality')
            ->where('student_academic_profiles.nationality', '!=', '')
            ->distinct()->orderBy('student_academic_profiles.nationality')
            ->pluck('student_academic_profiles.nationality');

        return $this->pageData([
            'students'      => $students,
            'my_interest_ids' => $myInterestIds,
            'nationalities' => $nationalities,
            'filters'       => compact('degree', 'nationality', 'field', 'lang_test'),
        ])->pageView('student_explore');
    }

    // -------------------------------------------------------
    // GET /{lang}/student_explore/view/{memberId} — full student profile
    // -------------------------------------------------------
    public function view($memberId = 0)
    {
        $memberId = (int)$memberId;
        if (!$memberId) {
            $this->doRedirect($this->toURL('student_explore'));
            return;
        }

        $profile = DB::table('student_academic_profiles as sap')
            ->join('member as m', 'm.id', '=', 'sap.member_id')
            ->where('sap.member_id', $memberId)
            ->where('m.type', 1)
            ->where('m.status', '>', 0)
            ->where('sap.status', 1)
            ->select(
                'sap.*',
                'm.alias_name', 'm.first_name', 'm.last_name', 'm.avatar', 'm.coverphoto'
            )
            ->first();

        if (!$profile) {
            $this->doRedirect($this->toURL('student_explore'));
            return;
        }

        // Increment view count (only once per session per profile)
        $sessionKey = 'sp_viewed_' . $memberId;
        if (!session($sessionKey)) {
            DB::table('student_academic_profiles')
                ->where('member_id', $memberId)
                ->increment('profile_views');
            session([$sessionKey => true]);
        }

        $alreadyInterested = DB::table('institution_interests')
            ->where('institution_member_id', $this->_institutionId)
            ->where('student_member_id', $memberId)
            ->where('status', 1)
            ->exists();

        return $this->pageData([
            'profile'           => $profile,
            'already_interested' => $alreadyInterested,
        ])->pageView('student_profile_view');
    }

    // -------------------------------------------------------
    // POST /{lang}/student_explore/interest — express interest
    // -------------------------------------------------------
    public function interest()
    {
        // Require institution login to express interest
        if (empty($this->_current_member) || (int)($this->_current_member['type'] ?? 0) !== 3) {
            return response()->json(['status' => 401, 'message' => 'Please log in as an institution account to use this feature.']);
        }

        if (!request()->isMethod('POST')) {
            return response()->json(['status' => 405]);
        }

        $studentId = (int)request()->input('student_id', 0);
        if (!$studentId) {
            return response()->json(['status' => 400, 'message' => 'Invalid request']);
        }

        // Verify student exists and has a profile
        $student = DB::table('member')
            ->where('id', $studentId)
            ->where('type', 1)
            ->where('status', '>', 0)
            ->first();

        if (!$student) {
            return response()->json(['status' => 404, 'message' => 'Student not found']);
        }

        try {
            // Upsert the interest record
            $existing = DB::table('institution_interests')
                ->where('institution_member_id', $this->_institutionId)
                ->where('student_member_id', $studentId)
                ->first();

            if ($existing) {
                if ($existing->status == 1) {
                    // Already interested
                    return response()->json(['status' => 200, 'message' => 'Already on your interest list', 'already' => true]);
                }
                // Reactivate
                DB::table('institution_interests')
                    ->where('id', $existing->id)
                    ->update(['status' => 1, 'updated_at' => now()]);
            } else {
                DB::table('institution_interests')->insert([
                    'institution_member_id' => $this->_institutionId,
                    'student_member_id'     => $studentId,
                    'status'                => 1,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Admin notification (once per institution-student pair)
            $notifExists = DB::table('admin_student_notifications')
                ->where('institution_member_id', $this->_institutionId)
                ->where('student_member_id', $studentId)
                ->exists();

            if (!$notifExists) {
                DB::table('admin_student_notifications')->insert([
                    'type'                  => 'institution_interest',
                    'institution_member_id' => $this->_institutionId,
                    'student_member_id'     => $studentId,
                    'is_read'               => 0,
                    'status'                => 'pending',
                    'email_sent'            => 0,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);

                // Send admin email notification
                $institution = DB::table('member')->where('id', $this->_institutionId)->first();
                $instName    = $institution->alias_name ?? ($institution->full_name ?? ('Institution #' . $this->_institutionId));
                $studentName = $student->alias_name ?? ($student->full_name ?? ('Student #' . $studentId));

                $subject = "🎓 New Student Interest — {$instName}";
                $body = "
<p style='font-family:sans-serif;'>An institution has expressed interest in a student on <strong>AI-mmi</strong>.</p>
<table style='font-family:sans-serif;border-collapse:collapse;margin:16px 0;'>
  <tr><td style='padding:6px 16px 6px 0;color:#666;font-weight:600;'>Institution:</td><td style='padding:6px 0;font-weight:700;color:#002065;'>{$instName}</td></tr>
  <tr><td style='padding:6px 16px 6px 0;color:#666;font-weight:600;'>Student:</td><td style='padding:6px 0;font-weight:700;color:#002065;'>{$studentName}</td></tr>
  <tr><td style='padding:6px 16px 6px 0;color:#666;font-weight:600;'>Date:</td><td style='padding:6px 0;'>" . now()->format('d M Y, H:i') . "</td></tr>
</table>
<p style='font-family:sans-serif;'><a href='https://ai-mmi.com/admin/student_interests' style='background:#0066ff;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:700;'>View in Admin Panel →</a></p>
<p style='font-family:sans-serif;font-size:12px;color:#999;'>AI-mmi — Connecting students and institutions through trusted guidance.</p>
";
                $this->sendEmail('info@ai-mmi.com', $subject, $body);
            }

            return response()->json(['status' => 200, 'message' => 'Interest expressed! AI-mmi will be in touch.']);

        } catch (\Exception $e) {
            Log::error('Student_Explore::interest error: ' . $e->getMessage());
            return response()->json(['status' => 500, 'message' => 'Something went wrong, please try again.']);
        }
    }

    // -------------------------------------------------------
    // GET /{lang}/student_explore/my_interests — institution's shortlist
    // -------------------------------------------------------
    public function my_interests()
    {
        // Require institution login to view shortlist
        if (empty($this->_current_member) || (int)($this->_current_member['type'] ?? 0) !== 3) {
            header('Location: ' . $this->toURL('account_login'));
            exit();
        }

        $interests = DB::table('institution_interests as ii')
            ->join('member as m', 'm.id', '=', 'ii.student_member_id')
            ->leftJoin('student_academic_profiles as sap', 'sap.member_id', '=', 'ii.student_member_id')
            ->where('ii.institution_member_id', $this->_institutionId)
            ->where('ii.status', 1)
            ->select(
                'ii.id as interest_id', 'ii.created_at as interest_date',
                'sap.member_id', 'sap.headline', 'sap.nationality', 'sap.target_degree',
                'sap.target_fields', 'sap.target_intake_year', 'sap.language_scores',
                'm.alias_name', 'm.first_name', 'm.last_name', 'm.avatar',
                // Admin notification status for this pair
                DB::raw("(SELECT asn.status FROM app_admin_student_notifications asn
                    WHERE asn.institution_member_id = app_ii.institution_member_id
                      AND asn.student_member_id = app_ii.student_member_id
                    LIMIT 1) as notification_status")
            )
            ->orderByDesc('ii.created_at')
            ->get();

        return $this->pageData([
            'interests' => $interests,
        ])->pageView('student_my_interests');
    }
}
